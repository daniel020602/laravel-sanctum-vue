<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class TablePolicy
{
    /**
     * Create a new policy instance.
     */
    public function admin(User $user): Response
    {
        return $user->is_admin
            ? Response::allow()
            : Response::deny('You do not have permission to perform this action.');
    }
}
