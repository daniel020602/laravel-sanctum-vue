<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Week;
use App\Http\Requests\WeekRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Policies\WeekPolicy;
use Carbon\Carbon;

class WeeksController extends Controller
{
    function __construct()
    {
        $this->middleware('auth:sanctum')->except(['show']);
    }
    public function index()
    {
        if(Auth::user()->is_admin) {
            $weeks = Week::all();
        } else {

            $currentWeek = Carbon::now()->weekOfYear;
            $weeks = Week::where('week', '>=', $currentWeek)->get();
        }
        return response()->json([
            'message' => 'Weeks retrieved successfully',
            'data' => $weeks
        ]);
    }

    public function store(WeekRequest $request)
    {
        gate::authorize('admin', Auth::user());
        $validated = $request->validated();

        $week = Week::create($validated);

        return response()->json([
            'message' => 'Week created successfully',
            'data' => $week
        ], 201);
    }
    public function show(Week $week)
    {
        $user = Auth::user();

        if ($week->week < Carbon::now()->weekOfYear) {
            if ($user && $user->is_admin) {
                // Admins can view past weeks
                return response()->json([
                    'message' => 'Week retrieved successfully',
                    'data' => $week
                ]);
            } else {
                // Non-admins cannot view past weeks
                return response()->json([
                    'message' => 'You cannot view past weeks.',
                ], 403);
            }
        } else {
            // Anyone can view current/future weeks
            return response()->json([
                'message' => 'Week retrieved successfully',
                'data' => $week
            ]);
        }
    }
    public function update(WeekRequest $request, Week $week)
    {
        gate::authorize('admin', Auth::user());
        $validated = $request->validated();

        $week->update($validated);

        return response()->json([
            'message' => 'Week updated successfully',
            'data' => $week
        ]);
    }
    public function destroy(Week $week)
    {
        gate::authorize('admin', Auth::user());
        $week->delete();

        return response()->json([
            'message' => 'Week deleted successfully'
        ]);
    }
}
