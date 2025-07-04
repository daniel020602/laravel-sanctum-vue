<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Reservation;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Allow admin to modify a reservation if the code matches.
     */
    public function admin(User $user): Response
    {
        // Allow if user is an admin
        if ($user->is_admin) {
            return Response::allow();
        }

        // Deny otherwise
        return Response::deny('this action is reserved for administrators only.');
    }
}
