<?php

namespace App\Http\Requests\AttendanceSchedule;

use App\Enums\AttendanceScheduleEventType;
use App\Models\AttendanceSchedule;
use App\Services\Attendance\AttendanceScheduleHistoryGuard;
use App\Services\Attendance\AttendanceScheduleValidationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAttendanceScheduleRequest extends FormRequest
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
            /*
            |--------------------------------------------------------------------------
            | Scope inmutable
            |--------------------------------------------------------------------------
            */

            'academic_year_id' => [
                'prohibited',
            ],

            'educational_level_id' => [
                'prohibited',
            ],

            'grade_section_id' => [
                'prohibited',
            ],

            /*
            |--------------------------------------------------------------------------
            | Datos editables
            |--------------------------------------------------------------------------
            */

            'name' => [
                'sometimes',
                'string',
                'max:150',
            ],

            'valid_from' => [
                'sometimes',
                'date',
            ],

            'valid_until' => [
                'sometimes',
                'date',
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
                'sometimes',
                'array',
                'min:2',
            ],

            'events.*.day_of_week' => [
                'required_with:events',
                'integer',
                'between:1,7',
            ],

            'events.*.sequence' => [
                'required_with:events',
                'integer',
                'min:1',
                'max:20',
            ],

            'events.*.event_type' => [
                'required_with:events',
                Rule::enum(
                    AttendanceScheduleEventType::class
                ),
            ],

            'events.*.expected_time' => [
                'required_with:events',
                'date_format:H:i',
            ],

            'events.*.tolerance_minutes' => [
                'required_with:events',
                'integer',
                'min:0',
                'max:180',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var AttendanceSchedule|null $schedule */
                $schedule =
                    $this->route(
                        'attendance_schedule'
                    );

                if (!$schedule) {
                    return;
                }

                $data = $this->all();

                /*
                |--------------------------------------------------------------------------
                | Validar fecha final contra la fecha inicial efectiva
                |--------------------------------------------------------------------------
                */

                $validFrom =
                    $data['valid_from']
                    ?? $schedule->valid_from
                    ->format('Y-m-d');

                $validUntil =
                    $data['valid_until']
                    ?? $schedule->valid_until
                    ->format('Y-m-d');

                if (
                    $validUntil <
                    $validFrom
                ) {
                    $validator->errors()->add(
                        'valid_until',
                        'La fecha final debe ser igual o posterior a la fecha inicial.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Validaciones generales
                |--------------------------------------------------------------------------
                */

                app(
                    AttendanceScheduleValidationService::class
                )->validate(
                    $validator,
                    $data,
                    $schedule
                );

                /*
                |--------------------------------------------------------------------------
                | Protección histórica
                |--------------------------------------------------------------------------
                */

                app(
                    AttendanceScheduleHistoryGuard::class
                )->validateUpdate(
                    $validator,
                    $schedule,
                    $data
                );
            },
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.prohibited' =>
            'El año académico del horario no puede modificarse.',

            'educational_level_id.prohibited' =>
            'El nivel educativo del horario no puede modificarse.',

            'grade_section_id.prohibited' =>
            'El ámbito del horario no puede modificarse.',

            'events.min' =>
            'El horario debe contener al menos una entrada y una salida.',

            'events.*.expected_time.date_format' =>
            'La hora debe utilizar el formato HH:mm.',

            'events.*.tolerance_minutes.min' =>
            'La tolerancia no puede ser negativa.',
        ];
    }
}
