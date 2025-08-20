<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidDayValue;

class UpdateSubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $weekId = $this->route('sub')->week_id ?? $this->input('week_id');
        return [
            'day1' => ['sometimes', 'integer', new ValidDayValue('day1', $weekId)],
            'day2' => ['sometimes', 'integer', new ValidDayValue('day2', $weekId)],
            'day3' => ['sometimes', 'integer', new ValidDayValue('day3', $weekId)],
            'day4' => ['sometimes', 'integer', new ValidDayValue('day4', $weekId)],
            'day5' => ['sometimes', 'integer', new ValidDayValue('day5', $weekId)],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('status') || $this->has('week_id')) {
                $validator->errors()->add('week_id', 'Status or week cannot be set during subscription update.');
            }
        });
    }
}
