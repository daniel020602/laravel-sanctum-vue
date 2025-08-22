<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
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
        // when updating we should ignore the current menu id for the unique rule
        $menu = $this->route('menu');
        $ignoreId = null;
        if ($menu) {
            $ignoreId = is_object($menu) && property_exists($menu, 'id') ? $menu->id : $menu;
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $ignoreId ? Rule::unique('menus', 'name')->ignore($ignoreId) : 'unique:menus,name',
            ],
            'type' => ['required', 'in:soup,main,dessert'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
