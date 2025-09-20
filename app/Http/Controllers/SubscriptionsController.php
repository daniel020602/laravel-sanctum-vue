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
use Carbon\Carbon;

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
        $next = Carbon::now()->addWeek();
        if ((int)$week->week_number !== $next->weekOfYear || (int)$week->year !== $next->year) {
            return response()->json(['message' => 'Can only subscribe to next week'], 400);
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
        // Guard against editing choices for weeks that have already started.
        // Allow an exception when the week record represents the upcoming week (by week_number/year)
        $startDate = Carbon::parse($week->start_date);
        $today = Carbon::today();
        $nextWeek = Carbon::now()->addWeek();
        if ($startDate->lessThan($today)) {
            $isNextWeek = ((int)$week->week_number === $nextWeek->weekOfYear) && ((int)$week->year === $nextWeek->year);
            if (! $isNextWeek) {
                return response()->json(['message' => 'Can only change future week choices'], 400);
            }
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
        // Guard against deleting subscriptions for weeks that have already started.
        // Allow an exception when the week record represents the upcoming week (by week_number/year)
        $startDate = Carbon::parse($week->start_date);
        $today = Carbon::today();
        $nextWeek = Carbon::now()->addWeek();
        if ($startDate->lessThan($today)) {
            $isNextWeek = ((int)$week->week_number === $nextWeek->weekOfYear) && ((int)$week->year === $nextWeek->year);
            if (! $isNextWeek) {
                return response()->json(['message' => 'Can only change future week choices'], 400);
            }
        }
        // Delete associated choices first
        SubscriptionChoice::where('subscription_id', $subscription->id)->delete();
        $subscription->delete();

        return response()->json(['message' => 'Subscription deleted'], 200);
    }
    public function userWeek() {
        $user = Auth::user();
        $nextWeekNumber = now()->weekOfYear + 1;
        $currentYear = now()->year;
        $week = Week::where('week_number', $nextWeekNumber)
                    ->where('year', $currentYear)
                    ->first();

        if (! $week) {
            return response()->json(['message' => 'Week not found'], 404);
        }

        $subscription = Subscription::where('user_id', $user->id)
            ->where('week_id', $week->id)
            ->first();

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $subscriptionChoices = SubscriptionChoice::where('subscription_id', $subscription->id)->get();
        return response()->json([
            'subscription' => $subscription,
            'choices' => $subscriptionChoices
        ]);
    }

    // Admin: return counts per day and option for a given week
    public function weeklySummary($weekId, Request $request)
    {
        // only admins
        if (!($request->user() && $request->user()->is_admin)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $week = Week::find($weekId);
        if (! $week) {
            return response()->json(['message' => 'Week not found'], 404);
        }

        // Aggregate counts by day_of_week and option (a/b/c/soup)
        $rows = DB::table('subscription_choices as sc')
            ->join('week_menus as wm', 'sc.week_menu_id', '=', 'wm.id')
            ->select('wm.day_of_week as day', 'wm.option as option', DB::raw('count(sc.id) as count'))
            ->where('wm.week_id', $weekId)
            ->groupBy('wm.day_of_week', 'wm.option')
            ->get();

        // Normalize into days 1..5 and options a,b,c (and soup)
        $summary = [];
        for ($d = 1; $d <= 5; $d++) {
            $summary[$d] = ['soup' => 0, 'a' => 0, 'b' => 0, 'c' => 0];
        }

        foreach ($rows as $r) {
            $day = (int) $r->day;
            $opt = strtolower($r->option ?? 'a');
            if (! isset($summary[$day])) continue;
            if (! in_array($opt, ['soup','a','b','c'])) $opt = 'a';
            $summary[$day][$opt] = (int) $r->count;
        }

        return response()->json([
            'week_id' => $weekId,
            'week_number' => $week->week_number,
            'year' => $week->year,
            'summary' => $summary,
        ]);
    }
}
