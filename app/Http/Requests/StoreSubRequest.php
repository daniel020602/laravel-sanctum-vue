<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidDayValue;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


class StoreSubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $weekId = $this->input('week_id');
        $rules = [
            'week_id' => [
                'required',
                'exists:weeks,id',
                'integer',
                Rule::unique('subs')->where(fn($query) => $query->where('user_id', $this->user()->id)),
            ],
            'day1' => ['nullable', 'integer', new ValidDayValue('day1', $weekId)],
            'day2' => ['nullable', 'integer', new ValidDayValue('day2', $weekId)],
            'day3' => ['nullable', 'integer', new ValidDayValue('day3', $weekId)],
            'day4' => ['nullable', 'integer', new ValidDayValue('day4', $weekId)],
            'day5' => ['nullable', 'integer', new ValidDayValue('day5', $weekId)],
        ];

        Log::debug('SubRequest rules:', $rules); // 👈 log the rules

        return $rules;
    }


}
