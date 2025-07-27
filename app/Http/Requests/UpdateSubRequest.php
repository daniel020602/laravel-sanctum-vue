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
        $rules = [
            'day1' => ['nullable', 'integer', new ValidDayValue('day1')],
            'day2' => ['nullable', 'integer', new ValidDayValue('day2')],
            'day3' => ['nullable', 'integer', new ValidDayValue('day3')],
            'day4' => ['nullable', 'integer', new ValidDayValue('day4')],
            'day5' => ['nullable', 'integer', new ValidDayValue('day5')],
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
