<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Models\Subscription;

class SubscriptionPolicy
{
    /**
     * Create a new policy instance.
     */
    public function admin(User $user)
    {
        return $user->is_admin
            ? true
            : false;
    }
    public function owner(User $user, Subscription $subscription)
    {
        return $user->id === $subscription->user_id
            ? true
            : false;

    }
}
