<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'phone' => ['sometimes', 'string', 'max:15', 'unique:reservations,phone'], // Assuming phone is required for the reservation
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
                        $fail('This table is already reserved for the selected date and time.');
                    }
                },
            ],
            'email' => ['sometimes', 'email'], // Assuming email is required for the reservation
            'reservation_code' => ['nullable', 'string', 'max:10'], // Optional reservation code
            'name' => ['sometimes', 'string', 'max:255'], // Assuming name is required for the reservation
        ];
    }
}
