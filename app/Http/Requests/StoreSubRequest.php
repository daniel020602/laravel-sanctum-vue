<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidDayValue;
use Illuminate\Validation\Rule;


class StoreSubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week' => ['required', 'exists:weeks,id', 'integer', Rule::Unique('subs')->where(function ($query) {
                return $query->where('user_id', $this->user()->id);
            })],
            'day1' => ['nullable', 'integer', new ValidDayValue('day1')],
            'day2' => ['nullable', 'integer', new ValidDayValue('day2')],
            'day3' => ['nullable', 'integer', new ValidDayValue('day3')],
            'day4' => ['nullable', 'integer', new ValidDayValue('day4')],
            'day5' => ['nullable', 'integer', new ValidDayValue('day5')],
        ];
    }
}
