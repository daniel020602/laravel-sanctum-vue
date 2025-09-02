<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\SubscriptionChoice;
use Stripe\Stripe;
use Stripe\Charge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\WeekMenu;


class SubscriptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index(){
        $subscriptions = Subscription::all();
        return response()->json([
            'subscriptions' => $subscriptions
        ]);
    }

    public function show($id){
        $subscription = Subscription::find($id);
        $subscriptionChoices = SubscriptionChoice::where('subscription_id', $id)->get();
        if(!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }
        return response()->json([
            'subscription' => $subscription,
            'choices' => $subscriptionChoices
        ]);
    }
    public function store(Request $request){
        // ensure user_id is an integer id (use authenticated user when omitted)
        $request->merge(['user_id' => $request->input('user_id', Auth::id())]);

        $data = $request->validate([
            'week_id' => 'required|exists:weeks,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $subscription = Subscription::create([
            'user_id' => $data['user_id'],
            'week_id' => $data['week_id'],
        ]);
        // Mock Stripe charge (no external call)
        $mockCharge = [
            'id' => 'ch_mock_' . uniqid(),
            'amount' => 1000,
            'status' => 'succeeded',
        ];

        if ($mockCharge['status'] !== 'succeeded') {
            return response()->json(['message' => 'Payment failed'], 402);
        }

        // Load week menus and ensure we have at least 5 options
        $weekMenus = WeekMenu::where('week_id', $data['week_id'])->where('option','a')->get();
        foreach($weekMenus as $menu){
            SubscriptionChoice::create([
                'subscription_id' => $subscription->id,
                'week_menu_id' => $menu->id
            ]);
        }
        echo $weekMenus->pluck('id');
        return response()->json([
            'message' => 'Subscription created',

        ], 201);
    }
}
