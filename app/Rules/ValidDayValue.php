<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ValidDayValue implements ValidationRule
{
    protected string $day;

    public function __construct(string $day)
    {
        $this->day = $day;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table('weeks')
            ->where($this->day . 'a', $value)
            ->orWhere($this->day . 'b', $value)
            ->orWhere($this->day . 'c', $value)
            ->exists();

        if (! $exists) {
            $fail('The selected value for :attribute is invalid.');
        }
    }
    public function __toString(): string
    {
        return "ValidDayValue for {$this->day}";
    }
}
