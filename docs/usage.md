# Usage

Add the `HasFellowships` trait to your user model before using the Fellowship API.

```php
use EloquentWorks\Fellowship\Traits\HasFellowships;

class User extends Authenticatable
{
    use HasFellowships;
}
```

## Send a friend request

```php
$friendship = $user->sendFriendRequestTo($otherUser);
```

This creates a pending fellowship request.

If there is already a pending request, accepted friendship, block, or cooldown, a `LogicException` may be thrown.

## Accept a request

```php
$accepted = $user->acceptFriendRequestFrom($sender);
```

Returns `true` when the request was accepted.

Returns `false` if no pending request exists or the request has expired.

## Deny a request

```php
$denied = $user->denyFriendRequestFrom($sender);
```

Returns `true` when the request was denied.

## Cancel a sent request

```php
$canceled = $user->cancelFriendRequestTo($recipient);
```

Returns `true` when the pending sent request was canceled.

## Remove a friend

```php
$removed = $user->removeFriend($friend);
```

Returns `true` when an accepted friendship was removed.

## Block a user

```php
$block = $user->blockUser($otherUser);
```

Blocking creates or updates the fellowship record with the `blocked` status.

## Unblock a user

```php
$unblocked = $user->unblockUser($otherUser);
```

Only the user who created the block can unblock it.

## Get friends

```php
$friends = $user->friends();
```

Returns a collection of accepted friend user models.

## Friend request collections

```php
$incoming = $user->incomingFriendRequests();

$outgoing = $user->outgoingFriendRequests();
```

Both return non-expired pending fellowship models.

## Block collections

```php
$blockedUsers = $user->blockedUsers();

$blockedByUsers = $user->blockedByUsers();
```

## Mutual friends

```php
$mutualFriends = $user->mutualFriendsWith($otherUser);

$count = $user->mutualFriendsCountWith($otherUser);
```

## Counts

```php
$count = $user->friendsCount();
```

## Status helpers

```php
$user->isFriendsWith($otherUser);

$user->hasBlocked($otherUser);

$user->isBlockedBy($otherUser);

$user->hasPendingFriendRequestWith($otherUser);

$user->friendshipStatusWith($otherUser);

$user->friendshipWith($otherUser);

$user->canSendFriendRequestTo($otherUser);
```

## Relationship helpers

```php
$user->sentFriendships();

$user->receivedFriendships();

$user->acceptedSentFriendships();

$user->acceptedReceivedFriendships();

$user->pendingSentFriendships();

$user->pendingReceivedFriendships();
```

## Common flow

```php
// Nick sends Ava a request.
$nick->sendFriendRequestTo($ava);

// Ava sees incoming requests.
$requests = $ava->incomingFriendRequests();

// Ava accepts Nick's request.
$ava->acceptFriendRequestFrom($nick);

// Now both users are friends.
$nick->isFriendsWith($ava); // true
$ava->isFriendsWith($nick); // true
```
