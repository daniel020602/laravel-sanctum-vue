<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use App\Models\Week;

class ValidDayValue implements ValidationRule
{
    protected string $day;
    protected ?int $weekId;

    public function __construct(string $day, ?int $weekId = null)
    {
        $this->day = $day;
        $this->weekId = $weekId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get week_id if not passed (fallback to request)
        $weekId = $this->weekId ?? request()->input('week_id');

        if (!$weekId) {
            $fail('Week ID is required for validation.');
            return;
        }

        // Fetch the week model
        $week = Week::find($weekId);
        if (!$week) {
            $fail('Invalid week selected.');
            return;
        }

        // Check if week is past (assuming $week->week is the week number)
        $currentWeek = now()->weekOfYear;
        if ($week->week < $currentWeek) {
            $fail('You cannot subscribe to a past week.');
            return;
        }

        // Now check if the menu value exists in any of dayXa, dayXb, dayXc columns
        $exists = DB::table('weeks')
            ->where('id', $weekId)
            ->where(function ($query) use ($value) {
                $query->where($this->day . 'a', $value)
                      ->orWhere($this->day . 'b', $value)
                      ->orWhere($this->day . 'c', $value);
            })->exists();

        if (! $exists) {
            $fail('The selected value for ' . $attribute . ' is invalid.');
        }
    }
}
