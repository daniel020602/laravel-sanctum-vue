<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth; // Import the Auth facade
use Illuminate\Support\Facades\Gate; // For logging
use App\Models\OldReservation; // Assuming you have an OldReservation model


class ResAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index()
    {
        Gate::authorize('admin', Auth::user());
        $reservations = Reservation::all();
        return response()->json($reservations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $reservation = Reservation::create($request->all());
        return response()->json($reservation, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reservation = Reservation::findOrFail($id);
        return response()->json($reservation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update($request->all());
        return response()->json($reservation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();
        return response()->json(null, 204);
    }
    public function complete(Reservation $reservation)
    {
        $data = [];
        $data['name'] = $reservation->name;
        $data['email'] = $reservation->email;
        $data['phone'] = $reservation->phone;
        $data['date'] = $reservation->date;
        $data['time'] = $reservation->time;
        $data['table_id'] = $reservation->table_id;

        // Create a new OldReservation record
        OldReservation::create($data);

        // Delete the reservation from the current Reservation model
        $reservation->delete();

        return response()->json([
            'message' => 'Reservation completed successfully',
            'reservation' => $reservation // The reservation object before deletion
        ], 200);
    }
}
