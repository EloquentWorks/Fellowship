<?php

namespace EloquentWorks\Fellowship\Contracts;

use EloquentWorks\Fellowship\Models\Fellowship as FellowshipModel;
use Illuminate\Database\Eloquent\Model;

interface FellowshipUser
{
    /**
     * Send a friend request to another user.
     *
     * @param  Model  $user  The user to send the friend request to.
     * @return FellowshipModel Returns the fellowship model representing the friend request.
     */
    public function sendFriendRequestTo(Model $user): FellowshipModel;

    /**
     * Accept a friend request from another user.
     *
     * @param  Model  $user  The user to accept the friend request from.
     * @return bool Returns true if the friend request was accepted successfully, false otherwise.
     */
    public function acceptFriendRequestFrom(Model $user): bool;

    /**
     * Deny a received friend request from another user.
     *
     * @param  Model  $user  The user to deny the friend request from.
     * @return bool Returns true if the friend request was denied successfully, false otherwise.
     */
    public function denyFriendRequestFrom(Model $user): bool;

    /**
     * Cancel a sent friend request to another user.
     *
     * @param  Model  $user  The user to cancel the friend request to.
     * @return bool Returns true if the friend request was canceled successfully, false otherwise.
     */
    public function cancelFriendRequestTo(Model $user): bool;

    /**
     * Remove a friend from the user's friend list.
     *
     * @param  Model  $user  The user to remove from the friend list.
     * @return bool Returns true if the friend was removed successfully, false otherwise.
     */
    public function removeFriend(Model $user): bool;

    /**
     * Block a user, preventing them from sending friend requests or interacting with the user.
     *
     * @param  Model  $user  The user to block.
     * @return FellowshipModel Returns the fellowship model representing the block relationship.
     */
    public function blockUser(Model $user): FellowshipModel;

    /**
     * Unblock a previously blocked user, allowing them to send friend requests and interact with the user again.
     *
     * @param  Model  $user  The user to unblock.
     * @return bool Returns true if the user was unblocked successfully, false otherwise.
     */
    public function unblockUser(Model $user): bool;
}
