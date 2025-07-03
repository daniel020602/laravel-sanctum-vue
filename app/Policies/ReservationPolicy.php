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
    public function modify(User $user, Reservation $reservation, $code): Response
    {
        if ($user->is_admin && $reservation->reservation_code === $code) {
            return Response::allow();
        }
        return Response::deny('You do not have permission to modify this reservation.');
    }
    public function admin(User $user): Response
    {
        // Allow if user is an admin
        if ($user->is_admin) {
            return Response::allow();
        }

        // Deny otherwise
        return Response::deny('You do not have permission to create a reservation.');
    }
}
