<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sub;
use App\Models\Week; // Make sure to import the Week model
use Illuminate\Auth\Access\Response;

class SubPolicy
{
    /**
     * Determine if the user can store a new Sub.
     */
    public function admin(User $user): Response
    {
        // Allow if user is an admin
        if ($user->is_admin) {
            return Response::allow();
        }

        // Deny otherwise
        return Response::deny('You do not have permission to create a sub.');
    }

    /**
     * Determine if the user can update the Sub.
     */
    public function update(User $user, Sub $sub): Response
    {
        // Load the related Week model
        // Assuming Sub model has a belongsTo relationship with Week model,
        // you can access it directly as a dynamic property.
        $weekModel = $sub->week_id; // Access the related Week model directly

        // If the week does not exist, deny
        if (!$weekModel) {
            return Response::deny('Associated week not found for this sub.');
        }
        $weekOfSub= week::find($weekModel);// Assuming 'week' is the attribute that stores the week number
        // Get the current week number
        // Ensure that now()->weekOfYear aligns with how your Week model stores week numbers.
        $currentWeek = now()->weekOfYear;
        // Allow if user is admin
        if ($user->is_admin) {
            return Response::allow();
        }

        // Allow if user owns the sub AND the week is current or future
        if (
            $user->id == $sub->user_id &&
            $weekOfSub->week >= $currentWeek
        ) {
            return Response::allow();
        }

        // Deny for other cases (e.g., user doesn't own it, or it's a past week)
        return Response::deny('You do not have permission to update this sub. This might be due to not owning it or attempting to update a sub for a past week.');
    }
    public function show(User $user, Sub $sub): Response
    {
        // Allow if user is admin
        if ($user->is_admin) {
            return Response::allow();
        }

        // Allow if user owns the sub
        if ($user->id == $sub->user_id) {
            return Response::allow();
        }

        // Deny for other cases
        return Response::deny('You do not have permission to view this sub.');
    }
}