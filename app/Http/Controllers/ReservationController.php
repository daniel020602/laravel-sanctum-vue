<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation; // Assuming you have a Reservation model
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str; // For generating random strings
use Illuminate\Support\Facades\Auth; // Import the Auth facade
use Illuminate\Support\Facades\Log; // For logging
use App\Http\Requests\ReservationRequest; // Assuming you have a ReservationRequest for validation
use App\Mail\ReservationCode; // Assuming you have a ReservationCode Mailable

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'message' => 'List of reservations',
            'reservations' => Reservation::select('date', 'time', 'table_id')->get()
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReservationRequest $request)
    {
        $data = $request->validated();
        $ResCode = Str::random(10); // Use Str::random() directly
        $data['reservation_code'] = $ResCode; // Generate a random reservation code
        Mail::to($data['email'])->send(new ReservationCode($ResCode)); // Send the reservation code via email

        $reservation = Reservation::create($data);
        return response()->json([
            'message' => 'Reservation created successfully',
            'reservation' => $reservation
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $reservation = Reservation::findOrFail($id);
        $code = $request->input('reservation_code');
        if (!$code || $reservation->reservation_code !== $code) {
            return response()->json(['message' => 'Invalid reservation code'], 400);
        }
        return response()->json($reservation);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(ReservationRequest $request, string $id, Request $codeRequest)
    {
        $reservation = Reservation::findOrFail($id);
        $code = $codeRequest->input('reservation_code');
        if (!$code || $reservation->reservation_code !== $code) {
            return response()->json(['message' => 'Invalid reservation code'], 400);
        }
        $reservation->update($request->validated());
        return response()->json($reservation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $code = $request->input('reservation_code');
        $reservation = Reservation::findOrFail($id);
        if ($reservation->reservation_code !== $code && !$code) {
            return response()->json(['message' => 'Invalid reservation code'], 400);
        }
        $reservation->delete();
        return response()->json(null, 204);
    }

    /**
     * Confirm a reservation using a provided code.
     * This method is protected by the 'auth' middleware.
     */
    public function confirm(Reservation $reservation, Request $request)
    {
        // Remove echo $request;
        if ($reservation->reservation_code === $request->reservation_code && !$reservation->is_confirmed) {
            $reservation->is_confirmed = true; // Set the reservation as confirmed
            $reservation->save(); // Save the changes to the database

            return response()->json([
                'message' => 'Reservation confirmed successfully',
                'reservation' => $reservation // Return the single reservation, not all()
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid reservation code or reservation already confirmed'
            ], 400);
        }
    }

    /**
     * Marks a reservation as complete and moves it to OldReservation.
     */
    
}
