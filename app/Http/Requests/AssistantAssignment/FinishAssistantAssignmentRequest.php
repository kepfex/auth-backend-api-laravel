<?php

namespace App\Http\Requests\AssistantAssignment;

use Illuminate\Foundation\Http\FormRequest;

class FinishAssistantAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autenticación temporal.
        // Autorización granular pendiente para 7J-B.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.required' =>
                'Debes indicar la fecha de finalización.',

            'end_date.date_format' =>
                'La fecha debe tener el formato YYYY-MM-DD.',

            'end_date.before_or_equal' =>
                'No puedes finalizar una asignación '
                . 'con una fecha futura.',
        ];
    }
}
