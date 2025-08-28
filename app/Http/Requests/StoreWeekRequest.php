<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeekRequest extends FormRequest
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
    // App/Http/Requests/StoreWeekRequest.php
// Ez a fájl kezeli a validációt.

    public function rules(): array
    {
        $days = ['day1', 'day2', 'day3', 'day4', 'day5'];
        $options = ['soup', 'a', 'b', 'c'];

        $rules = [
            'year' => 'required|integer',
            'week_number' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'menus' => 'required|array',
        ];

        foreach ($days as $day) {
            // Ellenőrizd, hogy a nap mező (pl. menus.day1) létezik-e és tömb-e
            $rules["menus.{$day}"] = 'required|array';
            foreach ($options as $option) {
                // Ellenőrizd, hogy a napi opció mező (pl. menus.day1.soup) létezik-e és egész szám-e
                $rules["menus.{$day}.{$option}"] = 'required|integer|exists:menus,id';
            }
        }

        return $rules;
    }
}
