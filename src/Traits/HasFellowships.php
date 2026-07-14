<?php

namespace EloquentWorks\Fellowship\Traits;

use EloquentWorks\Fellowship\Events\FellowshipRemoved;
use EloquentWorks\Fellowship\Events\FellowshipRequestAccepted;
use EloquentWorks\Fellowship\Events\FellowshipRequestCanceled;
use EloquentWorks\Fellowship\Events\FellowshipRequestDenied;
use EloquentWorks\Fellowship\Events\FellowshipRequestExpired;
use EloquentWorks\Fellowship\Events\FellowshipRequestSent;
use EloquentWorks\Fellowship\Events\UserBlocked;
use EloquentWorks\Fellowship\Events\UserUnblocked;
use EloquentWorks\Fellowship\Models\Fellowship;
use EloquentWorks\Fellowship\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Trait HasFellowships
 *
 * Provides friendship functionality to Eloquent models.
 */
trait HasFellowships
{
    /**
     * Get friend requests sent by this user.
     *
     * @return HasMany Returns the sent friendships relationship.
     */
    public function sentFriendships(): HasMany
    {
        return $this->hasMany(
            config('fellowship.models.fellowship', Fellowship::class),
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
            config('fellowship.models.fellowship', Fellowship::class),
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

        // Get the IDs of users who have accepted fellowships with this user, both sent and received.
        $sentFellowshipIds = $this->sentFellowships()
            ->where('status', Status::ACCEPTED)
            ->pluck('recipient_id');
        $receivedFellowshipIds = $this->receivedFellowships()
            ->where('status', Status::ACCEPTED)
            ->pluck('sender_id');

        // Merge the sent and received fellowship IDs, remove duplicates, and retrieve the corresponding user models.
        return $userModel::query()
            ->whereIn($user->getKeyName(), $sentFellowshipIds->merge($receivedFellowshipIds)->unique())
            ->get();
    }

    /**
     * Send a fellowship request to another user.
     *
     * @param  Model  $user  The user to whom the fellowship request is being sent.
     * @return Fellowship Returns the created or updated fellowship model.
     */
    public function sendFellowshipRequestTo(Model $user): Fellowship
    {
        $this->guardAgainstSelfFellowship($user);

        // Use a database transaction to ensure atomicity of the fellowship request operation.
        return DB::transaction(function () use ($user): Fellowship {
            $pairKey = $this->fellowshipPairKey($user);

            // Check if there is an existing fellowship between the two users.
            $existingFellowship = $this->fellowshipBetween($user)
                ->lockForUpdate()
                ->first();

            // Handle different cases based on the existing fellowship status.
            if ($existingFellowship) {
                if ($existingFellowship->status === Status::ACCEPTED) {
                    throw new LogicException('You are already friends with this user.');
                }

                if ($existingFellowship->status === Status::PENDING && ! $this->fellowshipIsExpired($existingFellowship)) {
                    throw new LogicException('A friend request already exists between these users.');
                }

                if ($existingFellowship->status === Status::BLOCKED) {
                    throw new LogicException('A friend request cannot be sent between these users.');
                }

                if ($this->fellowshipIsInCooldown($existingFellowship)) {
                    throw new LogicException('You must wait before sending another friend request to this user.');
                }

                // Update the existing fellowship to 'pending' status and reset the accepted_at timestamp.
                $existingFellowship->forceFill([
                    'sender_id' => $this->getKey(),
                    'recipient_id' => $user->getKey(),
                    'pair_key' => $pairKey,
                    'status' => Status::PENDING,
                    'accepted_at' => null,
                    'expires_at' => $this->fellowshipRequestExpiresAt(),
                ])->save();

                $this->dispatchFellowshipEvent(new FellowshipRequestSent($existingFellowship));

                // Return the updated fellowship model with 'pending' status.
                return $existingFellowship;
            }

            // If no existing fellowship is found, create a new fellowship with 'pending' status.
            $fellowship = $this->sentFellowships()->create([
                'recipient_id' => $user->getKey(),
                'pair_key' => $pairKey,
                'status' => Status::PENDING,
                'expires_at' => $this->fellowshipRequestExpiresAt(),
            ]);

            // Dispatch an event indicating that a fellowship request has been sent.
            $this->dispatchFellowshipEvent(new FellowshipRequestSent($fellowship));

            // Return the newly created fellowship model with 'pending' status.
            return $fellowship;
        });
    }

    /**
     * Accept a fellowship request from another user.
     *
     * @param  Model  $user  The user who sent the fellowship request.
     * @return bool Returns true if the fellowship request was accepted, false otherwise.
     */
    public function acceptFellowshipRequestFrom(Model $user): bool
    {
        $this->guardAgainstSelfFellowship($user);

        // Find the pending fellowship request from the specified user.
        return DB::transaction(function () use ($user): bool {
            $fellowship = $this->receivedFellowships()
                ->where('sender_id', $user->getKey())
                ->where('status', Status::PENDING)
                ->lockForUpdate()
                ->first();

            // If no pending fellowship request is found, return false.
            if (! $fellowship) {
                return false;
            }

            // If the fellowship request has an expiration date and it has already passed, mark it as expired and return false.
            if ($this->fellowshipIsExpired($fellowship)) {
                $fellowship->forceFill([
                    'status' => Status::EXPIRED,
                    'accepted_at' => null,
                ])->save();

                $this->dispatchFellowshipEvent(new FellowshipRequestExpired($fellowship));

                return false;
            }

            // Update the fellowship status to 'accepted', set the accepted_at timestamp, and clear the expires_at timestamp.
            $saved = $fellowship->forceFill([
                'status' => Status::ACCEPTED,
                'accepted_at' => now(),
                'expires_at' => null,
            ])->save();

            if ($saved) {
                $this->dispatchFellowshipEvent(new FellowshipRequestAccepted($fellowship));
            }

            return $saved;
        });
    }

    /**
     * Deny a fellowship request from another user.
     *
     * @param  Model  $user  The user who sent the fellowship request.
     * @return bool Returns true if the fellowship request was successfully denied, false otherwise.
     */
    public function denyFellowshipRequestFrom(Model $user): bool
    {
        $this->guardAgainstSelfFellowship($user);

        return DB::transaction(function () use ($user): bool {
            // Find the pending fellowship request from the specified user.
            $fellowship = $this->receivedFellowships()
                ->where('sender_id', $user->getKey())
                ->where('status', Status::PENDING)
                ->lockForUpdate()
                ->first();

            // If no pending fellowship request is found, return false.
            if (! $fellowship) {
                return false;
            }

            // Update the fellowship status to 'denied' and reset the accepted_at timestamp for the pending request.
            $saved = $fellowship->forceFill([
                'status' => Status::DENIED,
                'accepted_at' => null,
                'expires_at' => null,
            ])->save();

            if ($saved) {
                $this->dispatchFellowshipEvent(new FellowshipRequestDenied($fellowship));
            }

            return $saved;
        });
    }

    /**
     * Cancel a pending fellowship request sent to another user.
     *
     * @param  Model  $user  The user to whom the fellowship request was sent.
     * @return bool Returns true if the fellowship request was successfully canceled, false otherwise.
     */
    public function cancelFellowshipRequestTo(Model $user): bool
    {
        $this->guardAgainstSelfFellowship($user);

        return DB::transaction(function () use ($user): bool {
            // Find the pending fellowship request sent to the specified user.
            $fellowship = $this->sentFellowships()
                ->where('recipient_id', $user->getKey())
                ->where('status', Status::PENDING)
                ->lockForUpdate()
                ->first();

            // If no pending fellowship request is found, return false.
            if (! $fellowship) {
                return false;
            }

            // Cancel the pending fellowship request by updating its status to 'canceled' and resetting the accepted_at timestamp.
            $saved = $fellowship->forceFill([
                'status' => Status::CANCELED,
                'accepted_at' => null,
                'expires_at' => null,
            ])->save();

            if ($saved) {
                $this->dispatchFellowshipEvent(new FellowshipRequestCanceled($fellowship));
            }

            return $saved;
        });
    }

    /**
     * Block a user, preventing any future friend requests or interactions.
     *
     * @param  Model  $user  The user to block.
     * @return Fellowship Returns the updated or created friendship model with 'blocked' status.
     */
    public function blockUser(Model $user): Fellowship
    {
        $this->guardAgainstSelfFellowship($user);

        // If there is an existing fellowship, update its status to 'blocked'. Otherwise, create a new fellowship with 'blocked' status.
        return DB::transaction(function () use ($user): Fellowship {
            $existingFellowship = $this->fellowshipBetween($user)
                ->lockForUpdate()
                ->first();

            // If an existing fellowship is found, update its status to 'blocked' and reset the accepted_at timestamp.
            if ($existingFellowship) {
                $existingFellowship->forceFill([
                    'sender_id' => $this->getKey(),
                    'recipient_id' => $user->getKey(),
                    'pair_key' => $this->fellowshipPairKey($user),
                    'status' => Status::BLOCKED,
                    'accepted_at' => null,
                    'expires_at' => null,
                ])->save();

                $this->dispatchFellowshipEvent(new UserBlocked($existingFellowship));

                // Return the updated fellowship model with 'blocked' status.
                return $existingFellowship;
            }

            // If no existing fellowship is found, create a new fellowship with 'blocked' status.
            $fellowship = $this->sentFellowships()->create([
                'recipient_id' => $user->getKey(),
                'pair_key' => $this->fellowshipPairKey($user),
                'status' => Status::BLOCKED,
                'accepted_at' => null,
                'expires_at' => null,
            ]);

            $this->dispatchFellowshipEvent(new UserBlocked($fellowship));

            return $fellowship;
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
        $this->guardAgainstSelfFellowship($user);

        // Use a database transaction to ensure atomicity of the unblock operation.
        return DB::transaction(function () use ($user): bool {
            $fellowship = $this->fellowshipBetween($user)
                ->lockForUpdate()
                ->first();

            // If no fellowship record is found, return false.
            if (! $fellowship) {
                return false;
            }

            // If the fellowship status is not 'blocked', return false.
            if ($fellowship->status !== Status::BLOCKED) {
                return false;
            }

            // Ensure that the current user is the sender of the block before allowing unblocking.
            if ((string) $fellowship->sender_id !== (string) $this->getKey()) {
                return false;
            }

            // Delete the fellowship record to unblock the user and return true if successful.
            $deleted = (bool) $fellowship->delete();

            if ($deleted) {
                $this->dispatchFellowshipEvent(new UserUnblocked($fellowship));
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
        $this->guardAgainstSelfFellowship($user);

        // Use a database transaction to ensure atomicity of the remove friend operation.
        return DB::transaction(function () use ($user): bool {
            $fellowship = $this->fellowshipBetween($user)
                ->lockForUpdate()
                ->first();

            // If no fellowship record is found, return false.
            if (! $fellowship) {
                return false;
            }

            // If the fellowship status is not 'accepted', return false.
            if ($fellowship->status !== Status::ACCEPTED) {
                return false;
            }

            // Delete the fellowship record to remove the friend and return true if successful.
            $deleted = (bool) $fellowship->delete();

            if ($deleted) {
                $this->dispatchFellowshipEvent(new FellowshipRemoved($fellowship));
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
        $this->guardAgainstSelfFellowship($user);

        // Check if there is an accepted fellowship between this user and the specified user.
        return $this->fellowshipBetween($user)
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
        $this->guardAgainstSelfFellowship($user);

        // Check if there is a blocked fellowship where this user created the block.
        return $this->fellowshipBetween($user)
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
        $this->guardAgainstSelfFellowship($user);

        // Check if there is a blocked fellowship where the other user created the block.
        return $this->fellowshipBetween($user)
            ->where('status', Status::BLOCKED)
            ->where('sender_id', $user->getKey())
            ->exists();
    }

    /**
     * Check if there is a pending fellowship request between this user and another user, considering expiration.
     *
     * @param  Model  $user  The other user to check for a pending fellowship request.
     * @return bool Returns true if there is a pending fellowship request, false otherwise.
     */
    public function hasPendingFellowshipRequestWith(Model $user): bool
    {
        $this->guardAgainstSelfFellowship($user);

        // Check if there is a pending fellowship between this user and the specified user, considering expiration.
        return $this->fellowshipBetween($user)
            ->where('status', Status::PENDING)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get the fellowship status with another user.
     *
     * @param  Model  $user  The other user to check.
     * @return string|null Returns the fellowship status or null if no fellowship exists.
     */
    public function fellowshipStatusWith(Model $user): ?string
    {
        $this->guardAgainstSelfFellowship($user);

        // Retrieve the fellowship status between this user and the specified user.
        return $this->fellowshipBetween($user)
            ->value('status');
    }

    /**
     * Get the fellowship record with another user.
     *
     * @param  Model  $user  The other user in the fellowship.
     * @return Fellowship|null Returns the fellowship model or null.
     */
    public function fellowshipWith(Model $user): ?Fellowship
    {
        $this->guardAgainstSelfFellowship($user);

        // Retrieve the fellowship record between this user and the specified user.
        return $this->fellowshipBetween($user)->first();
    }

    /**
     * Get incoming pending fellowship requests.
     *
     * @return Collection Returns a collection of incoming pending fellowship models.
     */
    public function incomingFellowshipRequests(): Collection
    {
        // Retrieve pending, non-expired fellowship requests received by this user.
        return $this->receivedFellowships()
            ->where('status', Status::PENDING)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();
    }

    /**
     * Get outgoing pending fellowship requests.
     *
     * @return Collection Returns a collection of outgoing pending fellowship models.
     */
    public function outgoingFellowshipRequests(): Collection
    {
        // Retrieve pending, non-expired fellowship requests sent by this user.
        return $this->sentFellowships()
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
        // Retrieve the user model class from the configuration.
        $userModel = config('auth.providers.users.model');

        // Create a new instance of the user model to access its key name.
        $user = new $userModel;

        // Get the IDs of users blocked by this user.
        $blockedUserIds = $this->sentFellowships()
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
        // Retrieve the user model class from the configuration.
        $userModel = config('auth.providers.users.model');

        // Create a new instance of the user model to access its key name.
        $user = new $userModel;

        // Get the IDs of users that blocked this user.
        $blockedByUserIds = $this->receivedFellowships()
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
     * @return Collection Returns a collection of mutual fellowship models.
     */
    public function mutualFellowshipsWith(Model $user): Collection
    {
        $this->guardAgainstSelfFellowship($user);

        // Get the IDs of this user's fellowships and the other user's fellowships.
        $myFellowshipIds = $this->fellowships()->pluck($this->getKeyName());

        // Get the IDs of the other user's fellowships.
        $theirFellowshipIds = $user->fellowships()->pluck($user->getKeyName());

        // Find the intersection of both users' fellowship IDs to get mutual fellowships.
        $mutualFellowshipIds = $myFellowshipIds->intersect($theirFellowshipIds)->values();

        // Retrieve the user model class from the configuration.
        $userModel = config('auth.providers.users.model');

        // Create a new instance of the user model to access its key name.
        $model = new $userModel;

        // Return a collection of mutual fellowship models based on the mutual fellowship IDs.
        return $userModel::query()
            ->whereIn($model->getKeyName(), $mutualFellowshipIds)
            ->get();
    }

    /**
     * Count mutual fellowships shared with another user.
     *
     * @param  Model  $user  The other user to compare fellowships with.
     * @return int Returns the mutual fellowship count.
     */
    public function mutualFellowshipsCountWith(Model $user): int
    {
        $this->guardAgainstSelfFellowship($user);

        // Count the mutual fellowships shared between this user and the specified user.
        return $this->mutualFellowshipsWith($user)->count();
    }

    /**
     * Count all accepted fellowships for this user.
     *
     * @return int Returns the total fellowship count.
     */
    public function fellowshipsCount(): int
    {
        // Count the number of accepted fellowships where this user was the sender.
        $sentCount = $this->sentFellowships()
            ->where('status', Status::ACCEPTED)
            ->count();

        // Count the number of accepted fellowships where this user was the recipient.
        $receivedCount = $this->receivedFellowships()
            ->where('status', Status::ACCEPTED)
            ->count();

        // Return the total count of accepted fellowships by summing sent and received counts.
        return $sentCount + $receivedCount;
    }

    /**
     * Check if this user can send a fellowship request to another user.
     *
     * @param  Model  $user  The user to check.
     * @return bool Returns true if a fellowship request can be sent.
     */
    public function canSendFellowshipRequestTo(Model $user): bool
    {
        $this->guardAgainstSelfFellowship($user);

        // Check if there is an existing fellowship between this user and the specified user.
        $fellowship = $this->fellowshipBetween($user)->first();

        // If no fellowship exists, the user can send a fellowship request.
        if (! $fellowship) {
            return true;
        }

        // If the fellowship is pending and not expired, the user cannot send a fellowship request.
        if ($fellowship->status === Status::PENDING && ! $this->fellowshipIsExpired($fellowship)) {
            return false;
        }

        // If the fellowship is already accepted, the user cannot send a fellowship request.
        if ($fellowship->status === Status::ACCEPTED) {
            return false;
        }

        // If the fellowship is blocked, the user cannot send a fellowship request.
        if ($fellowship->status === Status::BLOCKED) {
            return false;
        }

        // If the fellowship is in cooldown, the user cannot send a fellowship request yet.
        if ($this->fellowshipIsInCooldown($fellowship)) {
            return false;
        }

        // In all other cases (denied, canceled, expired outside cooldown), the user can send a fellowship request.
        return true;
    }

    /**
     * Get accepted fellowships where this user was the sender.
     *
     * @return HasMany Returns the accepted sent fellowships relationship.
     */
    public function acceptedSentFellowships(): HasMany
    {
        return $this->sentFellowships()
            ->where('status', Status::ACCEPTED);
    }

    /**
     * Get accepted fellowships where this user was the recipient.
     *
     * @return HasMany Returns the accepted received fellowships relationship.
     */
    public function acceptedReceivedFellowships(): HasMany
    {
        return $this->receivedFellowships()
            ->where('status', Status::ACCEPTED);
    }

    /**
     * Get pending fellowships where this user was the sender.
     *
     * @return HasMany Returns the pending sent fellowships relationship.
     */
    public function pendingSentFellowships(): HasMany
    {
        return $this->sentFellowships()
            ->where('status', Status::PENDING);
    }

    /**
     * Get pending fellowships where this user was the recipient.
     *
     * @return HasMany Returns the pending received fellowships relationship.
     */
    public function pendingReceivedFellowships(): HasMany
    {
        return $this->receivedFellowships()
            ->where('status', Status::PENDING);
    }

    /**
     * Build a query for the fellowship between this model and another model.
     *
     * @param  Model  $user  The other user in the fellowship.
     * @return Builder Returns a query builder for the fellowship.
     */
    protected function fellowshipBetween(Model $user): Builder
    {
        return $this->fellowshipModel()::query()
            ->where('pair_key', $this->fellowshipPairKey($user));
    }

    /**
     * Generate a unique key for the fellowship pair.
     *
     * @param  Model  $user  The other user in the fellowship.
     * @return string Returns a unique key representing the fellowship pair.
     */
    protected function fellowshipPairKey(Model $user): string
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
     * Guard against sending a fellowship request to oneself.
     *
     * @param  Model  $user  The user to check against.
     * @return void Returns nothing.
     *
     * @throws LogicException Throws an exception if the user is trying to send a fellowship request to themselves.
     */
    protected function guardAgainstSelfFellowship(Model $user): void
    {
        if ((string) $this->getKey() === (string) $user->getKey()) {
            throw new LogicException('You cannot send a fellowship request to yourself.');
        }
    }

    /**
     * Check if a fellowship request is expired.
     *
     * @param  Fellowship  $fellowship  The fellowship model to check.
     * @return bool Returns true if the fellowship request is expired, false otherwise.
     */
    protected function fellowshipIsExpired(Fellowship $fellowship): bool
    {
        return $fellowship->expires_at instanceof Carbon
            && $fellowship->expires_at->isPast();
    }

    /**
     * Check if a fellowship is in the resend cooldown window.
     *
     * @param  Fellowship  $fellowship  The fellowship model to check.
     * @return bool Returns true if another fellowship request cannot be sent yet, false otherwise.
     */
    protected function fellowshipIsInCooldown(Fellowship $fellowship): bool
    {
        $days = config('fellowship.request_cooldown_days');

        // If the configuration value is null or zero, cooldowns are disabled.
        if ($days === null || (int) $days <= 0) {
            return false;
        }

        // Only denied, canceled, and expired requests should trigger the resend cooldown.
        if (! in_array($fellowship->status, [Status::DENIED, Status::CANCELED, Status::EXPIRED], true)) {
            return false;
        }

        // If updated_at is not available as a date, do not apply a cooldown.
        if (! $fellowship->updated_at instanceof Carbon) {
            return false;
        }

        // The fellowship is in cooldown if it was updated within the configured cooldown window.
        return $fellowship->updated_at->gt(now()->subDays((int) $days));
    }

    /**
     * Get the fellowship model class name from the configuration.
     *
     * @return string Returns the fellowship model class name.
     */
    protected function fellowshipModel(): string
    {
        return config('fellowship.models.fellowship', Fellowship::class);
    }

    /**
     * Get the expiration date for fellowship requests based on the configuration.
     *
     * @return Carbon|null Returns the expiration date or null if fellowship requests do not expire.
     */
    protected function fellowshipRequestExpiresAt(): ?Carbon
    {
        $days = config('fellowship.expires_after_days');

        // If the configuration value is null, return null to indicate that fellowship requests do not expire.
        if ($days === null) {
            return null;
        }

        // Otherwise, return the current date and time plus the configured number of days for expiration.
        return now()->addDays((int) $days);
    }

    /**
     * Dispatch a fellowship event if package events are enabled.
     *
     * @param  object  $event  The event instance to dispatch.
     * @return void Returns nothing.
     */
    protected function dispatchFellowshipEvent(object $event): void
    {
        // If event dispatching is disabled in the configuration, do not dispatch the event.
        if (! config('fellowship.dispatch_events', true)) {
            return;
        }

        // Dispatch the fellowship event.
        event($event);
    }
}
