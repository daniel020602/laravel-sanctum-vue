<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeekRequest extends FormRequest
{
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
            'menus' => 'required|array',
        ];

        foreach ($days as $day) {
            // Ellenőrizd, hogy a nap mező (pl. menus.day1) létezik-e és tömb-e
            $rules["menus.{$day}"] = 'sometimes|array';
            foreach ($options as $option) {
                // Ellenőrizd, hogy a napi opció mező (pl. menus.day1.soup) létezik-e és egész szám-e
                $rules["menus.{$day}.{$option}"] = 'sometimes|integer|exists:menus,id';
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $menus = $this->input('menus', []);
            foreach ($menus as $day => $options) {
                if (!is_array($options)) continue;
                $ids = array_filter($options);
                if (count($ids) !== count(array_unique($ids))) {
                    $validator->errors()->add("menus.$day", "Duplicate menu detected for $day.");
                }
            }
        });
    }
}
