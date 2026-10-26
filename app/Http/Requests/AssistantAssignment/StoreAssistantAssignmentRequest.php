<?php

namespace App\Http\Requests\AssistantAssignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssistantAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Temporal: permisos específicos en 7J-B.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id'),
            ],

            'grade_section_id' => [
                'required',
                'integer',
                Rule::exists('grade_sections', 'id'),
            ],

            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' =>
            'Debes seleccionar un auxiliar.',

            'employee_id.exists' =>
            'El empleado seleccionado no existe.',

            'grade_section_id.required' =>
            'Debes seleccionar un grado y sección.',

            'grade_section_id.exists' =>
            'El grado y sección seleccionado no existe.',

            'start_date.required' =>
            'Debes indicar la fecha de inicio.',

            'end_date.after_or_equal' =>
            'La fecha de fin no puede ser anterior al inicio.',
        ];
    }
}
