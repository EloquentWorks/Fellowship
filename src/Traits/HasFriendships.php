<?php

namespace PixelError\Friendships\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;
use PixelError\Friendships\Events\FriendRequestAccepted;
use PixelError\Friendships\Events\FriendRequestCanceled;
use PixelError\Friendships\Events\FriendRequestDenied;
use PixelError\Friendships\Events\FriendRequestExpired;
use PixelError\Friendships\Events\FriendRequestSent;
use PixelError\Friendships\Events\FriendshipRemoved;
use PixelError\Friendships\Events\UserBlocked;
use PixelError\Friendships\Events\UserUnblocked;
use PixelError\Friendships\Models\Friendship;
use PixelError\Friendships\Status;

/**
 * Trait HasFriendships
 *
 * Provides friendship functionality to Eloquent models.
 */
trait HasFriendships
{
    /**
     * Get friend requests sent by this user.
     *
     * @return HasMany Returns the sent friendships relationship.
     */
    public function sentFriendships(): HasMany
    {
        return $this->hasMany(
            config('friendships.models.friendship', Friendship::class),
            'sender_id'
        );
    }

    /**
     * Get friend requests received by this user.
     *
     * @return HasMany Returns the received friendships relationship.
     */
    public function receivedFriendships(): HasMany
    {
        return $this->hasMany(
            config('friendships.models.friendship', Friendship::class),
            'recipient_id'
        );
    }

    /**
     * Get all friends of this user.
     *
     * @return \Illuminate\Support\Collection Returns a collection of friend user models.
     */
    public function friends(): Collection
    {
        $userModel = config('auth.providers.users.model');

        $user = new $userModel;

        // Get the IDs of users who have accepted friendships with this user, both sent and received.
        $sentFriendIds = $this->sentFriendships()
            ->where('status', Status::ACCEPTED)
            ->pluck('recipient_id');
        $receivedFriendIds = $this->receivedFriendships()
            ->where('status', Status::ACCEPTED)
            ->pluck('sender_id');

        // Merge the sent and received friend IDs, remove duplicates, and retrieve the corresponding user models.
        return $userModel::query()
            ->whereIn($user->getKeyName(), $sentFriendIds->merge($receivedFriendIds)->unique())
            ->get();
    }

    /**
     * Send a friend request to another user.
     *
     * @param  Model  $user  The user to whom the friend request is being sent.
     * @return Friendship Returns the created or updated friendship model.
     */
    public function sendFriendRequestTo(Model $user): Friendship
    {
        $this->guardAgainstSelfFriendship($user);

        // Use a database transaction to ensure atomicity of the friend request operation.
        return DB::transaction(function () use ($user): Friendship {
            $pairKey = $this->friendshipPairKey($user);

            // Check if there is an existing friendship between the two users.
            $existingFriendship = $this->friendshipBetween($user)
                ->lockForUpdate()
                ->first();

            // Handle different cases based on the existing friendship status.
            if ($existingFriendship) {
                if ($existingFriendship->status === Status::ACCEPTED) {
                    throw new LogicException('You are already friends with this user.');
                }

                if ($existingFriendship->status === Status::PENDING && ! $this->friendshipIsExpired($existingFriendship)) {
                    throw new LogicException('A friend request already exists between these users.');
                }

                if ($existingFriendship->status === Status::BLOCKED) {
                    throw new LogicException('A friend request cannot be sent between these users.');
                }

                if ($this->friendshipIsInCooldown($existingFriendship)) {
                    throw new LogicException('You must wait before sending another friend request to this user.');
                }

                // Update the existing friendship to 'pending' status and reset the accepted_at timestamp.
                $existingFriendship->forceFill([
                    'sender_id' => $this->getKey(),
                    'recipient_id' => $user->getKey(),
                    'pair_key' => $pairKey,
                    'status' => Status::PENDING,
                    'accepted_at' => null,
                    'expires_at' => $this->friendRequestExpiresAt(),
                ])->save();

                $this->dispatchFriendshipEvent(new FriendRequestSent($existingFriendship));

                // Return the updated friendship model with 'pending' status.
                return $existingFriendship;
            }

            // If no existing friendship is found, create a new friendship with 'pending' status.
            $friendship = $this->sentFriendships()->create([
                'recipient_id' => $user->getKey(),
                'pair_key' => $pairKey,
                'status' => Status::PENDING,
                'expires_at' => $this->friendRequestExpiresAt(),
            ]);

            $this->dispatchFriendshipEvent(new FriendRequestSent($friendship));

            return $friendship;
        });
    }

    /**
     * Accept a friend request from another user.
     *
     * @param  Model  $user  The user who sent the friend request.
     * @return bool Returns true if the friend request was accepted, false otherwise.
     */
    public function acceptFriendRequestFrom(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        // Find the pending friendship request from the specified user.
        return DB::transaction(function () use ($user): bool {
            $friendship = $this->receivedFriendships()
                ->where('sender_id', $user->getKey())
                ->where('status', Status::PENDING)
                ->lockForUpdate()
                ->first();

            // If no pending friendship request is found, return false.
            if (! $friendship) {
                return false;
            }

            // If the friendship request has an expiration date and it has already passed, mark it as expired and return false.
            if ($this->friendshipIsExpired($friendship)) {
                $friendship->forceFill([
                    'status' => Status::EXPIRED,
                    'accepted_at' => null,
                ])->save();

                $this->dispatchFriendshipEvent(new FriendRequestExpired($friendship));

                return false;
            }

            // Update the friendship status to 'accepted', set the accepted_at timestamp, and clear the expires_at timestamp.
            $saved = $friendship->forceFill([
                'status' => Status::ACCEPTED,
                'accepted_at' => now(),
                'expires_at' => null,
            ])->save();

            if ($saved) {
                $this->dispatchFriendshipEvent(new FriendRequestAccepted($friendship));
            }

            return $saved;
        });
    }

    /**
     * Deny a friend request from another user.
     *
     * @param  Model  $user  The user who sent the friend request.
     * @return bool Returns true if the friend request was successfully denied, false otherwise.
     */
    public function denyFriendRequestFrom(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        return DB::transaction(function () use ($user): bool {
            // Find the pending friendship request from the specified user.
            $friendship = $this->receivedFriendships()
                ->where('sender_id', $user->getKey())
                ->where('status', Status::PENDING)
                ->lockForUpdate()
                ->first();

            // If no pending friendship request is found, return false.
            if (! $friendship) {
                return false;
            }

            // Update the friendship status to 'denied' and reset the accepted_at timestamp for the pending request.
            $saved = $friendship->forceFill([
                'status' => Status::DENIED,
                'accepted_at' => null,
                'expires_at' => null,
            ])->save();

            if ($saved) {
                $this->dispatchFriendshipEvent(new FriendRequestDenied($friendship));
            }

            return $saved;
        });
    }

    /**
     * Cancel a pending friend request sent to another user.
     *
     * @param  Model  $user  The user to whom the friend request was sent.
     * @return bool Returns true if the friend request was successfully canceled, false otherwise.
     */
    public function cancelFriendRequestTo(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        return DB::transaction(function () use ($user): bool {
            // Find the pending friendship request sent to the specified user.
            $friendship = $this->sentFriendships()
                ->where('recipient_id', $user->getKey())
                ->where('status', Status::PENDING)
                ->lockForUpdate()
                ->first();

            // If no pending friendship request is found, return false.
            if (! $friendship) {
                return false;
            }

            // Cancel the pending friend request by updating its status to 'canceled' and resetting the accepted_at timestamp.
            $saved = $friendship->forceFill([
                'status' => Status::CANCELED,
                'accepted_at' => null,
                'expires_at' => null,
            ])->save();

            if ($saved) {
                $this->dispatchFriendshipEvent(new FriendRequestCanceled($friendship));
            }

            return $saved;
        });
    }

    /**
     * Block a user, preventing any future friend requests or interactions.
     *
     * @param  Model  $user  The user to block.
     * @return Friendship Returns the updated or created friendship model with 'blocked' status.
     */
    public function blockUser(Model $user): Friendship
    {
        $this->guardAgainstSelfFriendship($user);

        // If there is an existing friendship, update its status to 'blocked'. Otherwise, create a new friendship with 'blocked' status.
        return DB::transaction(function () use ($user): Friendship {
            $existingFriendship = $this->friendshipBetween($user)
                ->lockForUpdate()
                ->first();

            // If an existing friendship is found, update its status to 'blocked' and reset the accepted_at timestamp.
            if ($existingFriendship) {
                $existingFriendship->forceFill([
                    'sender_id' => $this->getKey(),
                    'recipient_id' => $user->getKey(),
                    'pair_key' => $this->friendshipPairKey($user),
                    'status' => Status::BLOCKED,
                    'accepted_at' => null,
                    'expires_at' => null,
                ])->save();

                $this->dispatchFriendshipEvent(new UserBlocked($existingFriendship));

                // Return the updated friendship model with 'blocked' status.
                return $existingFriendship;
            }

            // If no existing friendship is found, create a new friendship with 'blocked' status.
            $friendship = $this->sentFriendships()->create([
                'recipient_id' => $user->getKey(),
                'pair_key' => $this->friendshipPairKey($user),
                'status' => Status::BLOCKED,
                'accepted_at' => null,
                'expires_at' => null,
            ]);

            $this->dispatchFriendshipEvent(new UserBlocked($friendship));

            return $friendship;
        });
    }

    /**
     * Unblock a user that was previously blocked.
     *
     * @param  Model  $user  The user to unblock.
     * @return bool Returns true if the user was successfully unblocked, false otherwise.
     */
    public function unblockUser(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        // Use a database transaction to ensure atomicity of the unblock operation.
        return DB::transaction(function () use ($user): bool {
            $friendship = $this->friendshipBetween($user)
                ->lockForUpdate()
                ->first();

            // If no friendship record is found, return false.
            if (! $friendship) {
                return false;
            }

            // If the friendship status is not 'blocked', return false.
            if ($friendship->status !== Status::BLOCKED) {
                return false;
            }

            // Ensure that the current user is the sender of the block before allowing unblocking.
            if ((string) $friendship->sender_id !== (string) $this->getKey()) {
                return false;
            }

            // Delete the friendship record to unblock the user and return true if successful.
            $deleted = (bool) $friendship->delete();

            if ($deleted) {
                $this->dispatchFriendshipEvent(new UserUnblocked($friendship));
            }

            return $deleted;
        });
    }

    /**
     * Remove a friend from the user's friends list.
     *
     * @param  Model  $user  The user to remove from the friends list.
     * @return bool Returns true if the friend was successfully removed, false otherwise.
     */
    public function removeFriend(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        // Use a database transaction to ensure atomicity of the remove friend operation.
        return DB::transaction(function () use ($user): bool {
            $friendship = $this->friendshipBetween($user)
                ->lockForUpdate()
                ->first();

            // If no friendship record is found, return false.
            if (! $friendship) {
                return false;
            }

            // If the friendship status is not 'accepted', return false.
            if ($friendship->status !== Status::ACCEPTED) {
                return false;
            }

            // Delete the friendship record to remove the friend and return true if successful.
            $deleted = (bool) $friendship->delete();

            if ($deleted) {
                $this->dispatchFriendshipEvent(new FriendshipRemoved($friendship));
            }

            return $deleted;
        });
    }

    /**
     * Check if this user is friends with another user.
     *
     * @param  Model  $user  The other user to check friendship with.
     * @return bool Returns true if this user is friends with the specified user, false otherwise.
     */
    public function isFriendsWith(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        // Check if there is an accepted friendship between this user and the specified user.
        return $this->friendshipBetween($user)
            ->where('status', Status::ACCEPTED)
            ->exists();
    }

    /**
     * Check if this user has blocked another user.
     *
     * @param  Model  $user  The other user to check.
     * @return bool Returns true if this user has blocked the specified user, false otherwise.
     */
    public function hasBlocked(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        // Check if there is a blocked friendship where this user created the block.
        return $this->friendshipBetween($user)
            ->where('status', Status::BLOCKED)
            ->where('sender_id', $this->getKey())
            ->exists();
    }

    /**
     * Check if this user is blocked by another user.
     *
     * @param  Model  $user  The other user to check.
     * @return bool Returns true if this user is blocked by the specified user, false otherwise.
     */
    public function isBlockedBy(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        // Check if there is a blocked friendship where the other user created the block.
        return $this->friendshipBetween($user)
            ->where('status', Status::BLOCKED)
            ->where('sender_id', $user->getKey())
            ->exists();
    }

    /**
     * Check if there is a pending friend request between this user and another user, considering expiration.
     *
     * @param  Model  $user  The other user to check for a pending friend request.
     * @return bool Returns true if there is a pending friend request, false otherwise.
     */
    public function hasPendingFriendRequestWith(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        // Check if there is a pending friendship between this user and the specified user, considering expiration.
        return $this->friendshipBetween($user)
            ->where('status', Status::PENDING)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get the friendship status with another user.
     *
     * @param  Model  $user  The other user to check.
     * @return string|null Returns the friendship status or null if no friendship exists.
     */
    public function friendshipStatusWith(Model $user): ?string
    {
        $this->guardAgainstSelfFriendship($user);

        // Retrieve the friendship status between this user and the specified user.
        return $this->friendshipBetween($user)
            ->value('status');
    }

    /**
     * Get the friendship record with another user.
     *
     * @param  Model  $user  The other user in the friendship.
     * @return Friendship|null Returns the friendship model or null.
     */
    public function friendshipWith(Model $user): ?Friendship
    {
        $this->guardAgainstSelfFriendship($user);

        // Retrieve the friendship record between this user and the specified user.
        return $this->friendshipBetween($user)->first();
    }

    /**
     * Get incoming pending friend requests.
     *
     * @return Collection Returns a collection of incoming pending friendship models.
     */
    public function incomingFriendRequests(): Collection
    {
        // Retrieve pending, non-expired friend requests received by this user.
        return $this->receivedFriendships()
            ->where('status', Status::PENDING)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();
    }

    /**
     * Get outgoing pending friend requests.
     *
     * @return Collection Returns a collection of outgoing pending friendship models.
     */
    public function outgoingFriendRequests(): Collection
    {
        // Retrieve pending, non-expired friend requests sent by this user.
        return $this->sentFriendships()
            ->where('status', Status::PENDING)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();
    }

    /**
     * Get users blocked by this user.
     *
     * @return Collection Returns a collection of blocked user models.
     */
    public function blockedUsers(): Collection
    {
        $userModel = config('auth.providers.users.model');

        $user = new $userModel;

        // Get the IDs of users blocked by this user.
        $blockedUserIds = $this->sentFriendships()
            ->where('status', Status::BLOCKED)
            ->pluck('recipient_id');

        // Retrieve the blocked user models.
        return $userModel::query()
            ->whereIn($user->getKeyName(), $blockedUserIds)
            ->get();
    }

    /**
     * Get users who blocked this user.
     *
     * @return Collection Returns a collection of user models that blocked this user.
     */
    public function blockedByUsers(): Collection
    {
        $userModel = config('auth.providers.users.model');

        $user = new $userModel;

        // Get the IDs of users that blocked this user.
        $blockedByUserIds = $this->receivedFriendships()
            ->where('status', Status::BLOCKED)
            ->pluck('sender_id');

        // Retrieve the user models that blocked this user.
        return $userModel::query()
            ->whereIn($user->getKeyName(), $blockedByUserIds)
            ->get();
    }

    /**
     * Get mutual friends shared with another user.
     *
     * @param  Model  $user  The other user to compare friends with.
     * @return Collection Returns a collection of mutual friend models.
     */
    public function mutualFriendsWith(Model $user): Collection
    {
        $this->guardAgainstSelfFriendship($user);

        // Get the IDs of this user's friends and the other user's friends.
        $myFriendIds = $this->friends()->pluck($this->getKeyName());

        // Get the IDs of the other user's friends.
        $theirFriendIds = $user->friends()->pluck($user->getKeyName());

        // Find the intersection of both users' friend IDs to get mutual friends.
        $mutualFriendIds = $myFriendIds->intersect($theirFriendIds)->values();

        // Retrieve the user model class from the configuration.
        $userModel = config('auth.providers.users.model');

        // Create a new instance of the user model to access its key name.
        $model = new $userModel;

        // Return a collection of mutual friend models based on the mutual friend IDs.
        return $userModel::query()
            ->whereIn($model->getKeyName(), $mutualFriendIds)
            ->get();
    }

    /**
     * Count mutual friends shared with another user.
     *
     * @param  Model  $user  The other user to compare friends with.
     * @return int Returns the mutual friend count.
     */
    public function mutualFriendsCountWith(Model $user): int
    {
        $this->guardAgainstSelfFriendship($user);

        // Count the mutual friends shared between this user and the specified user.
        return $this->mutualFriendsWith($user)->count();
    }

    /**
     * Count all accepted friends for this user.
     *
     * @return int Returns the total friend count.
     */
    public function friendsCount(): int
    {
        // Count the number of accepted friendships where this user was the sender.
        $sentCount = $this->sentFriendships()
            ->where('status', Status::ACCEPTED)
            ->count();

        // Count the number of accepted friendships where this user was the recipient.
        $receivedCount = $this->receivedFriendships()
            ->where('status', Status::ACCEPTED)
            ->count();

        // Return the total count of accepted friends by summing sent and received counts.
        return $sentCount + $receivedCount;
    }

    /**
     * Check if this user can send a friend request to another user.
     *
     * @param  Model  $user  The user to check.
     * @return bool Returns true if a friend request can be sent.
     */
    public function canSendFriendRequestTo(Model $user): bool
    {
        $this->guardAgainstSelfFriendship($user);

        // Check if there is an existing friendship between this user and the specified user.
        $friendship = $this->friendshipBetween($user)->first();

        // If no friendship exists, the user can send a friend request.
        if (! $friendship) {
            return true;
        }

        // If the friendship is pending and not expired, the user cannot send a friend request.
        if ($friendship->status === Status::PENDING && ! $this->friendshipIsExpired($friendship)) {
            return false;
        }

        // If the friendship is already accepted, the user cannot send a friend request.
        if ($friendship->status === Status::ACCEPTED) {
            return false;
        }

        // If the friendship is blocked, the user cannot send a friend request.
        if ($friendship->status === Status::BLOCKED) {
            return false;
        }

        // If the friendship is in cooldown, the user cannot send a friend request yet.
        if ($this->friendshipIsInCooldown($friendship)) {
            return false;
        }

        // In all other cases (denied, canceled, expired outside cooldown), the user can send a friend request.
        return true;
    }

    /**
     * Get accepted friendships where this user was the sender.
     *
     * @return HasMany Returns the accepted sent friendships relationship.
     */
    public function acceptedSentFriendships(): HasMany
    {
        return $this->sentFriendships()
            ->where('status', Status::ACCEPTED);
    }

    /**
     * Get accepted friendships where this user was the recipient.
     *
     * @return HasMany Returns the accepted received friendships relationship.
     */
    public function acceptedReceivedFriendships(): HasMany
    {
        return $this->receivedFriendships()
            ->where('status', Status::ACCEPTED);
    }

    /**
     * Get pending friendships where this user was the sender.
     *
     * @return HasMany Returns the pending sent friendships relationship.
     */
    public function pendingSentFriendships(): HasMany
    {
        return $this->sentFriendships()
            ->where('status', Status::PENDING);
    }

    /**
     * Get pending friendships where this user was the recipient.
     *
     * @return HasMany Returns the pending received friendships relationship.
     */
    public function pendingReceivedFriendships(): HasMany
    {
        return $this->receivedFriendships()
            ->where('status', Status::PENDING);
    }

    /**
     * Build a query for the friendship between this model and another model.
     *
     * @param  Model  $user  The other user in the friendship.
     * @return Builder Returns a query builder for the friendship.
     */
    protected function friendshipBetween(Model $user): Builder
    {
        return $this->friendshipModel()::query()
            ->where('pair_key', $this->friendshipPairKey($user));
    }

    /**
     * Generate a unique key for the friendship pair.
     *
     * @param  Model  $user  The other user in the friendship.
     * @return string Returns a unique key representing the friendship pair.
     */
    protected function friendshipPairKey(Model $user): string
    {
        $ids = [
            (string) $this->getKey(),
            (string) $user->getKey(),
        ];

        // Sort the IDs to ensure the pair key is consistent regardless of order
        sort($ids, SORT_NATURAL);

        // Join the sorted IDs with a colon to create a unique pair key
        return implode(':', $ids);
    }

    /**
     * Guard against sending a friend request to oneself.
     *
     * @param  Model  $user  The user to check against.
     * @return void Returns nothing.
     *
     * @throws LogicException Throws an exception if the user is trying to send a friend request to themselves.
     */
    protected function guardAgainstSelfFriendship(Model $user): void
    {
        if ((string) $this->getKey() === (string) $user->getKey()) {
            throw new LogicException('You cannot send a friend request to yourself.');
        }
    }

    /**
     * Check if a friendship request is expired.
     *
     * @param  Friendship  $friendship  The friendship model to check.
     * @return bool Returns true if the friendship request is expired, false otherwise.
     */
    protected function friendshipIsExpired(Friendship $friendship): bool
    {
        return $friendship->expires_at instanceof Carbon
            && $friendship->expires_at->isPast();
    }

    /**
     * Check if a friendship is in the resend cooldown window.
     *
     * @param  Friendship  $friendship  The friendship model to check.
     * @return bool Returns true if another friend request cannot be sent yet, false otherwise.
     */
    protected function friendshipIsInCooldown(Friendship $friendship): bool
    {
        $days = config('friendships.request_cooldown_days');

        // If the configuration value is null or zero, cooldowns are disabled.
        if ($days === null || (int) $days <= 0) {
            return false;
        }

        // Only denied, canceled, and expired requests should trigger the resend cooldown.
        if (! in_array($friendship->status, [Status::DENIED, Status::CANCELED, Status::EXPIRED], true)) {
            return false;
        }

        // If updated_at is not available as a date, do not apply a cooldown.
        if (! $friendship->updated_at instanceof Carbon) {
            return false;
        }

        // The friendship is in cooldown if it was updated within the configured cooldown window.
        return $friendship->updated_at->gt(now()->subDays((int) $days));
    }

    /**
     * Get the friendship model class name from the configuration.
     *
     * @return string Returns the friendship model class name.
     */
    protected function friendshipModel(): string
    {
        return config('friendships.models.friendship', Friendship::class);
    }

    /**
     * Get the expiration date for friend requests based on the configuration.
     *
     * @return Carbon|null Returns the expiration date or null if friend requests do not expire.
     */
    protected function friendRequestExpiresAt(): ?Carbon
    {
        $days = config('friendships.expires_after_days');

        // If the configuration value is null, return null to indicate that friend requests do not expire.
        if ($days === null) {
            return null;
        }

        // Otherwise, return the current date and time plus the configured number of days for expiration.
        return now()->addDays((int) $days);
    }

    /**
     * Dispatch a friendship event if package events are enabled.
     *
     * @param  object  $event  The event instance to dispatch.
     * @return void Returns nothing.
     */
    protected function dispatchFriendshipEvent(object $event): void
    {
        // If event dispatching is disabled in the configuration, do not dispatch the event.
        if (! config('friendships.dispatch_events', true)) {
            return;
        }

        // Dispatch the friendship event.
        event($event);
    }
}
