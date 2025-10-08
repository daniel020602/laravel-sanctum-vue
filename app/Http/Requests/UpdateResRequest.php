<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date'],
            'time' => ['sometimes', 'date_format:H:i'],
            'table_id' => [
                'sometimes',
                'exists:tables,id',
                // Ensure only one reservation per table, date, and time
                function ($attribute, $value, $fail) {
                    $date = $this->input('date');
                    $time = $this->input('time');
                    $exists = \App\Models\Reservation::where('table_id', $value)
                        ->where('date', $date)
                        ->where('time', $time)
                        ->exists();
                    if ($exists) {
                        $fail('This table is already reserved for the selected date and time. NÉZD MILYEN CSENDES AZ OLASZ CSALÁD A MÁSIK ASZTALNÁL!');
                    }
                },
            ],
        ];
    }
}
