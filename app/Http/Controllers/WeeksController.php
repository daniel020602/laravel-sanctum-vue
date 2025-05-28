<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Week;
use App\Http\Requests\WeekRequest;

class WeeksController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Welcome to the Weeks API',
            'status' => 'success'
        ]);    
    }

    public function store(WeekRequest $request)
    {
        $validated = $request->validated();

        $week = Week::create($validated);

        return response()->json([
            'message' => 'Week created successfully',
            'data' => $week
        ], 201);
    }
}
