<?php

namespace EloquentWorks\Fellowship\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LogicException;

class FellowshipController extends Controller
{
    /**
     * Send a friend request to another user.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to send the friend request to (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function send(Request $request, mixed $user): RedirectResponse
    {
        // Send a friend request to the specified user by calling the sendFriendRequestTo method on the authenticated user.
        $request->user()->sendFriendRequestTo($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the request has been sent.
        return back()->with('fellowship.status', 'request_sent');
    }

    /**
     * Accept a friend request from another user.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to accept the friend request from (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function accept(Request $request, mixed $user): RedirectResponse
    {
        // Accept the friend request from the specified user by calling the acceptFriendRequestFrom method on the authenticated user.
        $request->user()->acceptFriendRequestFrom($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the request has been accepted.
        return back()->with('fellowship.status', 'request_accepted');
    }

    /**
     * Deny a received friend request.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to deny the friend request from (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function deny(Request $request, mixed $user): RedirectResponse
    {
        // Deny the friend request from the specified user by calling the denyFriendRequestFrom method on the authenticated user.
        $request->user()->denyFriendRequestFrom($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the request has been denied.
        return back()->with('fellowship.status', 'request_denied');
    }

    /**
     * Cancel a sent friend request.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to cancel the friend request to (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function cancel(Request $request, mixed $user): RedirectResponse
    {
        $request->user()->cancelFriendRequestTo($this->resolveUser($user));

        return back()->with('fellowship.status', 'request_canceled');
    }

    /**
     * Remove a friend from the authenticated user's friends list.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to remove (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function remove(Request $request, mixed $user): RedirectResponse
    {
        // Remove the specified user from the authenticated user's friends list by calling the removeFriend method.
        $request->user()->removeFriend($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the friend has been removed.
        return back()->with('fellowship.status', 'friend_removed');
    }

    /**
     * Block a user.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to block (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function block(Request $request, mixed $user): RedirectResponse
    {
        // Block the specified user by calling the blockUser method on the authenticated user.
        $request->user()->blockUser($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the user has been blocked.
        return back()->with('fellowship.status', 'user_blocked');
    }

    /**
     * Unblock a user.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to unblock (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function unblock(Request $request, mixed $user): RedirectResponse
    {
        // Unblock the specified user by calling the unblockUser method on the authenticated user.
        $request->user()->unblockUser($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the user has been unblocked.
        return back()->with('fellowship.status', 'user_unblocked');
    }

    /**
     * Resolve the user model from the given value.
     *
     * @param  mixed  $value  The value to resolve (can be a user ID or an instance of the user model).
     * @return Model Returns the resolved user model instance.
     *
     * @throws LogicException If the configured user model is invalid or cannot be resolved.
     */
    protected function resolveUser(mixed $value): Model
    {
        // If the value is already an instance of the Eloquent Model, return it directly.
        if ($value instanceof Model) {
            return $value;
        }

        // Retrieve the user model class from the authentication configuration.
        $model = config('auth.providers.users.model');

        // Ensure the configured user model is a valid string and a subclass of the Eloquent Model class.
        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            throw new LogicException('Unable to resolve the configured user model.');
        }

        // Attempt to find the user by ID or throw a 404 error if not found.
        return $model::query()->findOrFail($value);
    }
}
