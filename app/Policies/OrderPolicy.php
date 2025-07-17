<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Create a new policy instance.
     */
    public function admin(User $user)
    {
        return $user->is_admin
            ? Response::allow()
            : Response::deny('You do not have admin access.');
    }
    public function ownerOrAdmin(User $user, $order)
    {
        return $user->id === $order->user_id || $user->is_admin
            ? Response::allow()
            : Response::deny('You do not own this order.');
    }
}
