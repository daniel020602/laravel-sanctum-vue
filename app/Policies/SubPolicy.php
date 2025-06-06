<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubPolicy
{
    /**
     * Determine if the user can store a new Sub.
     */
    public function store(User $user): Response
    {
        // Allow if user is an admin
        if ($user->is_admin) {
            return Response::allow();
        }

        // Deny otherwise
        return Response::deny('You do not have permission to create a sub.');
    }
}
