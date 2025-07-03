<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation; // Assuming you have a Reservation model
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str; // For generating random strings
use App\Models\OldReservation; // Assuming you have an OldReservation model
use Termwind\Components\Ol;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'store','confirm','update','show','delete']); // Allow unauthenticated users to view and create reservations
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data=$request->all();
        $ResCode=Str()->random(10);
        $data['reservation_code'] = $ResCode; // Generate a random reservation code
        Mail::to($data['email'])->send(new \App\Mail\ReservationCode($data['reservation_code'])); // Send the reservation code via email
        $reservation = Reservation::create($data);
        return response()->json([
            'message' => 'Reservation created successfully',
            'reservation' => $reservation
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function confirm(Reservation $reservation, Request $request)
    {
        echo $request;
        if ($reservation->reservation_code === $request->reservation_code&& !$reservation->is_confirmed) {
            $reservation->is_confirmed = true; // Set the reservation as confirmed
            $reservation->save(); // Save the changes to the database

            return response()->json([
                'message' => 'Reservation confirmed successfully',
                'reservation' => $reservation->all(),
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid reservation code or reservation already confirmed'
            ], 400);
           
        }
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
        OldReservation::create($data); // Save the reservation to OldReservation model
        $reservation->delete(); // Delete the reservation from the Reservation model
        return response()->json([
            'message' => 'Reservation completed successfully',
            'reservation' => $reservation
        ], 200);
    }
}
