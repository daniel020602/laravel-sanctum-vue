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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                // ensure phone is unique across reservations except for the current record
                Rule::unique('reservations', 'phone')->ignore($this->route('res_admin') ?? $this->route('reservation') ?? $this->route('id')),
            ],
            'reservation_code' => [
                'sometimes',
                'string',
                'max:50',
                // Determine the route parameter name used for this resource and ignore it for unique check
                Rule::unique('reservations', 'reservation_code')->ignore($this->route('res_admin') ?? $this->route('reservation') ?? $this->route('id')),
            ],
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
