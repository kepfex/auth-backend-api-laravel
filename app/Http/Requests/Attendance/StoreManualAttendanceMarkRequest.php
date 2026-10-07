<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreManualAttendanceMarkRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enrollment_id' => [
                'required',
                'integer',
                'exists:enrollments,id',
            ],

            'attendance_schedule_event_id' => [
                'required',
                'integer',
                'exists:attendance_schedule_events,id',
            ],

            'recorded_at' => [
                'required',
                'date',
            ],

            'observation' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'enrollment_id.required' =>
            'Debe seleccionar una matrícula.',

            'enrollment_id.exists' =>
            'La matrícula seleccionada no existe.',

            'attendance_schedule_event_id.required' =>
            'Debe seleccionar un evento del horario.',

            'attendance_schedule_event_id.exists' =>
            'El evento seleccionado no existe.',

            'recorded_at.required' =>
            'La fecha y hora de la marcación son obligatorias.',

            'recorded_at.date' =>
            'La fecha y hora de la marcación no son válidas.',

            'observation.max' =>
            'La observación no puede superar los 2000 caracteres.',
        ];
    }
}
