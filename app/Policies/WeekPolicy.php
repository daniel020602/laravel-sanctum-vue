<?php

namespace App\Policies;

use App\Models\Week;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Carbon\Carbon;

class WeekPolicy
{
    public function admin(User $user, Week $week): Response
    {
        
        return $user->is_admin ? Response::allow() : Response::deny('You do not have admin access.');
    }
    public function view(User $user, Week $week): Response
    {
        $currentWeek = Carbon::now()->weekOfYear;
        if ($currentWeek<= (int)$week->week||$user->is_admin) {
            return Response::allow();
        }
        return Response::deny('You cannot view past weeks.');
    }
}