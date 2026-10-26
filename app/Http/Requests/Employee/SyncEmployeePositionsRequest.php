<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncEmployeePositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'position_ids' => [
                'present',
                'array',
                'max:20',
            ],

            'position_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('positions', 'id')
                    ->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'position_ids.present' =>
            'Debes enviar el campo position_ids.',

            'position_ids.array' =>
            'Los cargos deben enviarse como un arreglo.',

            'position_ids.*.distinct' =>
            'No puedes seleccionar un mismo cargo varias veces.',

            'position_ids.*.exists' =>
            'Uno de los cargos no existe o está inactivo.',
        ];
    }
}
