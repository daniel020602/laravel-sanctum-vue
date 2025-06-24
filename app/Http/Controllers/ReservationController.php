<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation; // Assuming you have a Reservation model
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str; // For generating random strings

class ReservationController extends Controller
{
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
                'reservation' => $reservation
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid reservation code or reservation already confirmed'
            ], 400);
           
        }
    }
}
