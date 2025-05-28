<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\userSub;
use App\Models\weeklyMenu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class userSubController extends Controller
{

    /**
     * userSubController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function createUserSelection(request $request)
    {
        $data = $request->all();
        userSub::create($data);
        return response()->json([
            'user_id' => $request->user_id,
            'week' => $request->week,
            'day1' => $request->day1,
            'day2' => $request->day2,
            'day3' => $request->day3,
            'day4' => $request->day4,
            'day5' => $request->day5,
        ]);
    }
    public function createWeeklyMenu(request $request)
    {
        weeklyMenu::create($request->all());
        return response()->json([
            'week' => $request->week,
            'selection' => $request->selection,
        ]);
    }
}
