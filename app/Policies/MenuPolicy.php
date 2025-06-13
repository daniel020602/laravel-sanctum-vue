<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Models\Menu;

class MenuPolicy
{
    /**
     * Create a new policy instance.
     */
    public function admin(User $user):Response
    {
        // Allow if user is an admin
        if ($user->is_admin) {
            return Response::allow();
        }

        // Deny otherwise
        return Response::deny('You do not have permission to create a menu.');
    }
}
