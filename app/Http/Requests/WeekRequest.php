<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Change to `true` to allow validation unless you override it with policy
        return true;
    }

    public function rules(): array
    {
        return [
            'week' => ['required', 'integer', 'min:1', 'max:51'],

            'soup' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'soup'),
            ],

            'day1a' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day1b' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day1c' => [
                'nullable',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],

            'day2a' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day2b' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day2c' => [
                'nullable',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],

            'day3a' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day3b' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day3c' => [
                'nullable',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],

            'day4a' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day4b' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day4c' => [
                'nullable',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],

            'day5a' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day5b' => [
                'required',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
            'day5c' => [
                'nullable',
                Rule::exists('menus', 'id')->where('type', 'main'),
            ],
        ];
    }
}
