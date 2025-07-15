<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ValidDayValue
{
    protected string $day;

    public function __construct(string $day)
    {
        $this->day = $day;
    }

    public function passes($attribute, $value): bool
    {
        return DB::table('weeks')
            ->where($this->day . 'a', $value)
            ->orWhere($this->day . 'b', $value)
            ->orWhere($this->day . 'c', $value)
            ->exists();
    }

    public function message(): string
    {
        return 'The selected value for :attribute is invalid.';
    }
}
