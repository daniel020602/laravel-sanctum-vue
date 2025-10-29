<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Week;
use App\Models\Menu;
use App\Models\WeekMenu;
use App\Models\User;
use App\Http\Requests\StoreWeekRequest;
use App\Http\Requests\UpdateWeekRequest;
use Illuminate\Support\Facades\DB;
// removed unused debug import

class WeeksController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['nextWeek']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        // If admin, return all weeks; else, only weeks where week_number <= current week
        if ($user && $user->is_admin) {
            $weeks = Week::orderBy('start_date', 'asc')->get();
        } else {
            $currentWeek = now()->weekOfYear;
            $weeks = Week::where('week_number', '>=', $currentWeek)
                ->orderBy('start_date', 'asc')
                ->get();
        }
        return response()->json([
            'weeks' => $weeks
        ]);
    }
    public function show($id)
    {
        $user = request()->user();
        $week = Week::findOrFail($id);

        if (!($user && $user->is_admin)) {
            $currentWeek = now()->weekOfYear;
            if ($week->week_number < $currentWeek) {
                return response()->json(['message' => 'Access denied to past weeks.'], 403);
            }
        }

        $weekMenus = WeekMenu::where('week_id', $id)->get();
        return response()->json([
            'week' => $week,
            'menus' => $weekMenus
        ]);
    }
    public function store(StoreWeekRequest $request)
    {
        $this->authorize('admin', Week::class);

        $menus = $request->input('menus', []);

        return DB::transaction(function () use ($request, $menus) {
            // create the week
            $week = Week::create([
                'year' => $request->year,
                'week_number' => $request->week_number,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            $days = ['day1', 'day2', 'day3', 'day4', 'day5'];
            $options = ['soup', 'a', 'b', 'c'];

            $records = [];

            foreach ($days as $i => $day) {
                if (!isset($menus[$day]) || !is_array($menus[$day])) continue;
                foreach ($options as $option) {
                    if (!isset($menus[$day][$option])) continue;

                    $records[] = [
                        'week_id' => $week->id,
                        'menu_id' => $menus[$day][$option],
                        'day_of_week' => $i + 1,
                        'option' => $option,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($records)) {
                // Use upsert to insert or update by the unique key (week_id, day_of_week, option)
                DB::table('week_menus')->upsert(
                    $records,
                    ['week_id', 'day_of_week', 'option'],
                    ['menu_id', 'updated_at']
                );
            }

            // load related menus for response
            $weekMenus = DB::table('week_menus')
                ->where('week_id', $week->id)
                ->get();

            return response()->json([
                'message' => 'Week and menus created successfully',
                'data' => [
                    'week' => $week,
                    'week_menus' => $weekMenus,
                ]
            ], 201);
        });
    }
    public function update(UpdateWeekRequest $request, $id)
    {
        $this->authorize('admin', Week::class);

        $week = Week::findOrFail($id);
        $menus = $request->input('menus', []);

        return DB::transaction(function () use ($week, $menus) {
            $days = ['day1', 'day2', 'day3', 'day4', 'day5'];
            $options = ['soup', 'a', 'b', 'c'];

            $records = [];

            foreach ($days as $i => $day) {
                if (!isset($menus[$day]) || !is_array($menus[$day])) continue;
                foreach ($options as $option) {
                    if (!isset($menus[$day][$option])) continue;

                    $records[] = [
                        'week_id' => $week->id,
                        'menu_id' => $menus[$day][$option],
                        'day_of_week' => $i + 1,
                        'option' => $option,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($records)) {
                // Upsert to update or insert menu options for the week
                DB::table('week_menus')->upsert(
                    $records,
                    ['week_id', 'day_of_week', 'option'],
                    ['menu_id', 'updated_at']
                );
            }

            // load related menus for response
            $weekMenus = DB::table('week_menus')
                ->where('week_id', $week->id)
                ->get();

            return response()->json([
                'message' => 'Week menus updated successfully',
                'data' => [
                    'week' => $week,
                    'week_menus' => $weekMenus,
                ]
            ]);
        });
    }
    public function destroy($id)
    {
        $this->authorize('admin', Week::class);

        $week = Week::findOrFail($id);

        // Delete related week_menus entries
        WeekMenu::where('week_id', $week->id)->delete();

        // Delete the week itself
        $week->delete();

        return response()->json([
            'message' => 'Week and related menus deleted successfully.'
        ], 200);
    }
    public function nextWeek()
    {
        $user = request()->user();
        $currentWeekNumber = now()->weekOfYear;
        $nextWeekNumber = $currentWeekNumber + 1;
        $currentYear = now()->year;
        // If next week exceeds 52, roll over to week 1 of next year
        if ($nextWeekNumber > 52) {
            $nextWeekNumber = 1;
            $currentYear += 1;
        }

        $week = Week::where('week_number', $nextWeekNumber)
                    ->where('year', $currentYear)
                    ->first();

        if (! $week) {
            return response()->json(['message' => 'Next week not found'], 404);
        }

        $weekMenus = WeekMenu::where('week_id', $week->id)->get();

        return response()->json([
            'week' => $week,
            'menus' => $weekMenus
        ]);
    }
}
