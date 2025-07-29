<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
        $rules = [
            'day1' => ['sometimes', 'integer', new ValidDayValue('day1', $weekId)],
            'day2' => ['sometimes', 'integer', new ValidDayValue('day2', $weekId)],
            'day3' => ['sometimes', 'integer', new ValidDayValue('day3', $weekId)],
            'day4' => ['sometimes', 'integer', new ValidDayValue('day4', $weekId)],
            'day5' => ['sometimes', 'integer', new ValidDayValue('day5', $weekId)],
        ];

        if ($this->user()->is_admin) {
            $rules['week_id'] = [
                'required',
                'integer',
                'exists:weeks,id',
                Rule::unique('subs')
                    ->ignore($this->route('subs'))
                    ->where(fn ($q) => $q->where('user_id', $this->user_id)),
            ];
        } else {
            // Prevent regular users from changing the week
            $rules['week_id'] = ['prohibited'];
        }

        return $rules;
    }
}
