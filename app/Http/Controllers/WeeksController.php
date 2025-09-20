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
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        // If admin, return all weeks; else, only weeks where week_number <= current week
        if ($user && $user->is_admin) {
            $weeks = Week::orderBy('start_date', 'asc')->get();
        } else {
            return response()->json(['message' => 'Access denied to past weeks.'], 403);
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
            
            // Ensure week_menus table reflects the provided payload. Use DB table calls
            // because the Week model defines the relation as `week_menus()` (snake_case).
            DB::table('week_menus')->where('week_id', $week->id)->delete();

            if (!empty($records)) {
                DB::table('week_menus')->insert($records);
            }

            $weekMenus = DB::table('week_menus')->where('week_id', $week->id)->get();

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
        if ($week->week_number < now()->week()+2) {
            return response()->json(['message' => 'Can only delete future weeks.'], 400);

        }
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
        $nextWeekNumber = now()->weekOfYear + 1;
            $week = Week::query()
                ->where('week_number', $nextWeekNumber)
                ->where('year', now()->year)
                ->first();

            $weekMenus = WeekMenu::where('week_id', $week->id)->get();
            return response()->json([
                'week' => $week,
                'menus' => $weekMenus
            ]);
    }
}
