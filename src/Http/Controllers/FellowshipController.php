<?php

namespace EloquentWorks\Fellowship\Http\Controllers;

use EloquentWorks\Fellowship\Contracts\FellowshipUser;
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
        // Send a fellowship request to the specified user by calling the sendFellowshipRequestTo method on the authenticated user.
        $this->actor($request)->sendFellowshipRequestTo($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the request has been sent.
        return back()->with('fellowship.status', 'request_sent');
    }

    /**
     * Accept a fellowship request from another user.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to accept the fellowship request from (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function accept(Request $request, mixed $user): RedirectResponse
    {
        // Accept the fellowship request from the specified user by calling the acceptFellowshipRequestFrom method on the authenticated user.
        $this->actor($request)->acceptFellowshipRequestFrom($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the request has been accepted.
        return back()->with('fellowship.status', 'request_accepted');
    }

    /**
     * Deny a received fellowship request.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to deny the fellowship request from (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function deny(Request $request, mixed $user): RedirectResponse
    {
        // Deny the fellowship request from the specified user by calling the denyFellowshipRequestFrom method on the authenticated user.
        $this->actor($request)->denyFellowshipRequestFrom($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the request has been denied.
        return back()->with('fellowship.status', 'request_denied');
    }

    /**
     * Cancel a sent fellowship request.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to cancel the fellowship request to (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function cancel(Request $request, mixed $user): RedirectResponse
    {
        // Cancel the sent fellowship request to the specified user by calling the cancelFellowshipRequestTo method on the authenticated user.
        $this->actor($request)->cancelFellowshipRequestTo($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the request has been canceled.
        return back()->with('fellowship.status', 'request_canceled');
    }

    /**
     * Remove a fellowship from the authenticated user's list.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  mixed  $user  The user to remove (can be a user ID or an instance of the user model).
     * @return RedirectResponse Returns a redirect response back to the previous page with a status message.
     */
    public function remove(Request $request, mixed $user): RedirectResponse
    {
        // Remove the specified user from the authenticated user's fellowship list by calling the removeFellowship method.
        $this->actor($request)->removeFellowship($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the fellowship has been removed.
        return back()->with('fellowship.status', 'fellowship_removed');
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
        $this->actor($request)->blockUser($this->resolveUser($user));

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
        $this->actor($request)->unblockUser($this->resolveUser($user));

        // Return a redirect response back to the previous page with a status message indicating that the user has been unblocked.
        return back()->with('fellowship.status', 'user_unblocked');
    }

    /**
     * Get the authenticated model using the Fellowship trait methods.
     *
     * Laravel can only type the authenticated user as the base auth user/model, but
     * this controller requires the consuming app's user model to use HasFellowships.
     * The runtime method checks keep the error message clear, while the PHPDoc
     * intersection tells PHPStan that the fellowship methods are available here.
     *
     * @return Model&FellowshipUser
     */
    protected function actor(Request $request): Model
    {
        // Retrieve the authenticated user from the request.
        $actor = $request->user();

        // Ensure that the authenticated user is an instance of the Eloquent Model class.
        if (! $actor instanceof Model) {
            throw new LogicException('The authenticated user must be an Eloquent model.');
        }

        // Check if the authenticated user model implements all required fellowship methods.
        foreach ($this->requiredFellowshipMethods() as $method) {
            if (! method_exists($actor, $method)) {
                throw new LogicException(
                    'The authenticated user model must use the HasFellowships trait.'
                );
            }
        }

        /** @var Model&FellowshipUser $actor */
        return $actor;
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

    /**
     * Get the list of required fellowship methods that the authenticated user model must implement.
     *
     * @return array Returns an array of method names that are required for the fellowship functionality.
     */
    protected function requiredFellowshipMethods(): array
    {
        return [
            'sendFellowshipRequestTo',
            'acceptFellowshipRequestFrom',
            'denyFellowshipRequestFrom',
            'cancelFellowshipRequestTo',
            'removeFellowship',
            'blockUser',
            'unblockUser',
        ];
    }
}
