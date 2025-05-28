<?php

namespace App\Policies;
use Illuminate\Auth\Access\Response;
use App\Models\Sub;
use App\Models\User;

class SubPolicy
{
    public function modify(User $user, Sub $sub): Response
    {
        if ($user->id === $sub->user_id|| $user->isAdmin()) {
            return Response::allow();
        }
        return Response::deny('You do not own this subscription.');
    }
}