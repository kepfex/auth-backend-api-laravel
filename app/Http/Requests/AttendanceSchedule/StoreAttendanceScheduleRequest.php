<?php

namespace App\Http\Requests\AttendanceSchedule;

use App\Enums\AttendanceScheduleEventType;
use App\Enums\Weekday;
use App\Services\Attendance\AttendanceScheduleValidationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceScheduleRequest extends FormRequest
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
            'academic_year_id' => [
                'required',
                'integer',
                'exists:academic_years,id',
            ],

            'educational_level_id' => [
                'required',
                'integer',
                'exists:educational_levels,id',
            ],

            'grade_section_id' => [
                'nullable',
                'integer',
                'exists:grade_sections,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'valid_from' => [
                'required',
                'date',
            ],

            'valid_until' => [
                'required',
                'date',
                'after_or_equal:valid_from',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Eventos
            |--------------------------------------------------------------------------
            */

            'events' => [
                'required',
                'array',
                'min:2',
            ],

            'events.*.day_of_week' => [
                'required',
                'integer',
                Rule::in(
                    Weekday::values()
                ),
            ],

            'events.*.sequence' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],

            'events.*.event_type' => [
                'required',
                Rule::enum(
                    AttendanceScheduleEventType::class
                ),
            ],

            'events.*.expected_time' => [
                'required',
                'date_format:H:i',
            ],

            'events.*.tolerance_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:180',
            ],
            'events.*.window_before_minutes' => [
                'sometimes',
                'integer',
                'min:0',
                'max:360',
            ],

            'events.*.window_after_minutes' => [
                'sometimes',
                'integer',
                'min:0',
                'max:360',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                app(
                    AttendanceScheduleValidationService::class
                )->validate(
                    $validator,
                    $this->all()
                );
            },
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' =>
            'Debe seleccionar un año académico.',

            'academic_year_id.exists' =>
            'El año académico seleccionado no existe.',

            'educational_level_id.required' =>
            'Debe seleccionar un nivel educativo.',

            'educational_level_id.exists' =>
            'El nivel educativo seleccionado no existe.',

            'grade_section_id.exists' =>
            'El aula seleccionada no existe.',

            'name.required' =>
            'El nombre del horario es obligatorio.',

            'valid_from.required' =>
            'La fecha de inicio es obligatoria.',

            'valid_until.required' =>
            'La fecha final es obligatoria.',

            'valid_until.after_or_equal' =>
            'La fecha final debe ser igual o posterior a la fecha inicial.',

            'events.required' =>
            'Debe configurar los eventos del horario.',

            'events.min' =>
            'El horario debe contener al menos una entrada y una salida.',

            'events.*.day_of_week.required' =>
            'El día del evento es obligatorio.',

            'events.*.expected_time.required' =>
            'La hora del evento es obligatoria.',

            'events.*.expected_time.date_format' =>
            'La hora debe utilizar el formato HH:mm.',

            'events.*.tolerance_minutes.min' =>
            'La tolerancia no puede ser negativa.',
        ];
    }
}
