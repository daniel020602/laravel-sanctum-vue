<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Sub;
use App\Http\Requests\StoreSubRequest;
use App\Http\Requests\UpdateSubRequest;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Charge;


class SubsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function store(StoreSubRequest $request)
    {
        // Authorize via the SubPolicy's store() method
        $data =$request->validated();
        $data['user_id'] = Auth::user()->id; // Set the authenticated user's ID
        Sub::create($data);
        return response()->json([
            'message' => 'Subscription created successfully',
            'data' => $data
        ], 201);
    }
    public function update(UpdateSubRequest $request, Sub $sub)
    {
        // Authorize via the SubPolicy's update() method
        $this->authorize('update', $sub);
        
        $data = $request->validated();
        $sub->update($data);
        
        return response()->json([
            'message' => 'Subscription updated successfully',
            'data' => $sub
        ], 200);
    }
    public function destroy(Sub $sub)
    {
        // Authorize via the SubPolicy's update() method
        $this->authorize('update', $sub);
        
        $sub->delete();
        
        return response()->json([
            'message' => 'Subscription deleted successfully'
        ], 200);
    }
    public function index()
    {
        if (Auth::user()->is_admin) {
            $subs = Sub::all();
        } else {
            // Non-admin users can only see their own subscriptions
            $subs = Sub::where('user_id', Auth::id())
                ->get();
        }
        return response()->json([
            'message' => 'Subscriptions retrieved successfully',
            'data' => $subs
        ]);
    }
    public function show(Sub $sub)
    {
        // Authorize via the SubPolicy's update() method
        $this->authorize('show', $sub);
        
        return response()->json([
            'message' => 'Subscription retrieved successfully',
            'data' => $sub
        ]);
    }
    public function pay(Sub $sub)
    {
        $this->authorize('update', $sub);

        Stripe::setApiKey(config('services.stripe.secret'));
        if ($sub->status === 'paid') {
            return response()->json(['message' => 'Subscription already paid'], 400);
        }

        $charge = Charge::create([
            'amount' => 1000, // ár centben (10.00)
            'currency' => 'usd',
            'source' => 'tok_visa', // teszt token
            'description' => "Payment for subscription #{$sub->id}",
        ]);

        $sub->status = 'paid';
        $sub->save();

        return response()->json([
            'message' => 'Payment processed',
            'charge_id' => $charge->id
        ], 200);
    }
}
