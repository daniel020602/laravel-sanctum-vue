<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation; // Assuming you have a Reservation model
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str; // For generating random strings
use App\Models\OldReservation; // Assuming you have an OldReservation model
use Illuminate\Support\Facades\Auth; // Import the Auth facade
use Illuminate\Support\Facades\Log; // For logging

class ReservationController extends Controller
{
    public function __construct()
    {
        // The 'confirm' method requires authentication.
        // The 'show' method does NOT require authentication by default,
        // as it can be accessed via reservation code.
        // If you want 'show' to also require authentication for admin access,
        // you would add it here: $this->middleware('auth')->only(['confirm', 'show']);
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'message' => 'List of reservations',
            'reservations' => Reservation::all()
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $ResCode = Str::random(10); // Use Str::random() directly
        $data['reservation_code'] = $ResCode; // Generate a random reservation code

        // Ensure you have a Mail facade setup and a Mail class for ReservationCode
        // Example: php artisan make:mail ReservationCode --markdown=emails.reservation-code
        // And in emails/reservation-code.blade.php, you can display the code.
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
    public function show(string $id, Request $request)
    {
        try {
            $reservation = Reservation::find($id);

            if (!$reservation) {
                return response()->json([
                    'message' => 'Reservation not found'
                ], 404);
            }

            $user = Auth::user();
            $code = $request->input('reservation_code');

            if (($user && $user->is_admin) || ($code && $code === $reservation->reservation_code)) {
                return response()->json([
                    'message' => 'Reservation details',
                    'reservation' => $reservation
                ], 200);
            }

            return response()->json([
                'message' => 'Unauthorized access to reservation details'
            ], 403);
        } catch (\Throwable $e) {
            echo "na itt a kert";
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Implementation for updating a reservation.
        // You would typically add authorization checks here as well.
        // For example, only admins or the user who made the reservation (if linked) can update.
        return response()->json(['message' => 'Update method not implemented'], 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Implementation for deleting a reservation.
        // Again, authorization checks are crucial here.
        return response()->json(['message' => 'Destroy method not implemented'], 501);
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
