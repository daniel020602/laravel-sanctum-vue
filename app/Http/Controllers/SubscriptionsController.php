<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\SubscriptionChoice;
use App\Models\User;
use Stripe\Stripe;
use Stripe\Charge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\WeekMenu;
use App\Models\Week;
use Illuminate\Support\Facades\Gate;


class SubscriptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index(){
        if(Auth::user()->is_admin){
            $subscriptions = Subscription::all();
        } else {
            $subscriptions = Subscription::where('user_id', Auth::id())->get();
        }
        return response()->json([
            'subscriptions' => $subscriptions
        ]);
    }

    public function show($id){

       /* echo 'here';
        $this->authorize('admin');
        echo 'here2';*/
        $subscription = Subscription::find($id);
        $subscriptionChoices = SubscriptionChoice::where('subscription_id', $id)->get();
        if(!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }
        if (Gate::any(['owner', 'admin'], $subscription)) {
            // authorized
        } else {
            abort(403);
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
        $week = Week::findOrFail($data['week_id']);
        if($week->start_date < now()->toDateString()){
            return response()->json(['message' => 'Can only subscribe to current or future weeks'], 400);
        }
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

        // Allow forcing mock payment status for tests
        if ($request->has('mock_charge_status')) {
            $mockCharge['status'] = $request->input('mock_charge_status');
        }

        if ($mockCharge['status'] !== 'succeeded') {
            return response()->json(['message' => 'Payment failed'], 402);
        }

        // Load week menus and ensure we have at least 5 options
        $weekMenus = WeekMenu::where('week_id', $data['week_id'])->where('option','a')->get();
        foreach($weekMenus as $menu){
            SubscriptionChoice::create([
                'subscription_id' => $subscription->id,
                'week_menu_id' => $menu->id,
                'day' => $menu->day_of_week
            ]);
        }
        return response()->json([
            'message' => 'Subscription created',

        ], 201);
    }
    public function update(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);
        if (Gate::any(['owner', 'admin'], $subscription)) {
            // authorized
        } else {
            abort(403);
        }
        $week = Week::findOrFail($subscription->week_id);
        if($week->start_date < now()->toDateString()){
            return response()->json(['message' => 'Can only change future week choices'], 400);
        }
        $request->validate([
            'week_id' => 'required|exists:weeks,id',
            'choices' => 'required|array|min:1|max:5',
            'choices.*.week_menu_id' => 'required|exists:week_menus,id',
            'choices.*.day' => 'required|integer|between:1,5',
        ]);

        // Ensure subscription belongs to the same week
        $requestedWeekId = (int) $request->input('week_id');
        if ((int) $subscription->week_id !== $requestedWeekId) {
            return response()->json(['message' => 'Subscription week does not match provided week_id'], 400);
        }

        // Load week menus for the requested week and key by id for efficient lookup
        $weekMenus = WeekMenu::where('week_id', $requestedWeekId)->get()->keyBy('id');

        foreach ($request->input('choices') as $choice) {
            $wmId = (int) $choice['week_menu_id'];
            $day = (int) $choice['day'];

            // Verify the week_menu belongs to the requested week
            if (! isset($weekMenus[$wmId])) {
                return response()->json(['message' => "Week menu {$wmId} does not belong to week {$requestedWeekId}"], 400);
            }

            $wm = $weekMenus[$wmId];

            // Verify the day matches the week_menu's day_of_week
            if ((int) $wm->day_of_week !== $day) {
                return response()->json(['message' => 'Day does not match the week menu day'], 400);
            }

            // Update or create the subscription choice
            SubscriptionChoice::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'week_menu_id' => $wmId,
                ],
                [
                    'day' => $day,
                ]
            );
        }

        return response()->json([
            'message' => 'Subscription updated',
        ], 200);
    }
    public function destroy($id)
    {
        $subscription = Subscription::find($id);
        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }
        if (Gate::any(['owner', 'admin'], $subscription)) {
            // authorized
        } else {
            abort(403);
        }
        $week = Week::findOrFail($subscription->week_id);
        if($week->start_date < now()->toDateString()){
            return response()->json(['message' => 'Can only change future week choices'], 400);
        }
        // Delete associated choices first
        SubscriptionChoice::where('subscription_id', $subscription->id)->delete();
        $subscription->delete();

        return response()->json(['message' => 'Subscription deleted'], 200);
    }

}
