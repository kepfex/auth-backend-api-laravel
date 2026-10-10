<?php

namespace App\Http\Requests\AttendanceCalendarException;

use App\Enums\AttendanceScheduleEventType;
use App\Enums\AttendanceScheduleType;
use App\Models\AttendanceCalendarException;
use App\Services\Attendance\AttendanceScheduleValidationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreScheduleOverrideExceptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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

            'date' => [
                'required',
                'date',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Horario excepcional
            |--------------------------------------------------------------------------
            */

            'schedule' => [
                'required',
                'array',
            ],

            'schedule.name' => [
                'required',
                'string',
                'max:150',
            ],

            'schedule.events' => [
                'required',
                'array',
                'min:2',
            ],

            'schedule.events.*.event_type' => [
                'required',
                Rule::enum(
                    AttendanceScheduleEventType::class
                ),
            ],

            'schedule.events.*.expected_time' => [
                'required',
                'date_format:H:i',
            ],

            'schedule.events.*.tolerance_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:180',
            ],

            'schedule.events.*.window_before_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:360',
            ],

            'schedule.events.*.window_after_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:360',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ) {
                if (
                    $validator
                    ->errors()
                    ->isNotEmpty()
                ) {
                    return;
                }

                $data =
                    $this->all();

                $date =
                    CarbonImmutable::parse(
                        $data['date']
                    );

                /*
                |--------------------------------------------------------------------------
                | Construimos virtualmente el Schedule
                | para reutilizar la validación que ya tenemos.
                |--------------------------------------------------------------------------
                */

                $events =
                    collect(
                        $data['schedule']['events']
                    )
                    ->values()
                    ->map(
                        fn(
                            array $event,
                            int $index
                        ) => [
                            ...$event,

                            'day_of_week' =>
                            $date
                                ->dayOfWeekIso,

                            'sequence' =>
                            $index + 1,
                        ]
                    )
                    ->all();

                $scheduleData = [
                    'academic_year_id' =>
                    $data['academic_year_id'],

                    'educational_level_id' =>
                    $data['educational_level_id'],

                    'grade_section_id' =>
                    $data['grade_section_id']
                        ?? null,

                    'name' =>
                    $data['schedule']['name'],

                    'schedule_type' =>
                    AttendanceScheduleType::Override
                        ->value,

                    'valid_from' =>
                    $data['date'],

                    'valid_until' =>
                    $data['date'],

                    'is_active' =>
                    $data['is_active']
                        ?? true,

                    'events' =>
                    $events,
                ];

                app(
                    AttendanceScheduleValidationService::class
                )->validate(
                    $validator,
                    $scheduleData
                );

                /*
                |--------------------------------------------------------------------------
                | Excepción activa duplicada
                |--------------------------------------------------------------------------
                */

                $duplicateQuery =
                    AttendanceCalendarException::query()
                    ->where(
                        'academic_year_id',
                        $data['academic_year_id']
                    )
                    ->whereDate(
                        'date',
                        $data['date']
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->where(
                        'educational_level_id',
                        $data['educational_level_id']
                    );

                if (
                    empty($data['grade_section_id'])
                ) {
                    $duplicateQuery
                        ->whereNull(
                            'grade_section_id'
                        );
                } else {
                    $duplicateQuery
                        ->where(
                            'grade_section_id',
                            $data['grade_section_id']
                        );
                }

                if (
                    $duplicateQuery->exists()
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'date',
                            'Ya existe una excepción activa para esta fecha y ámbito.'
                        );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'educational_level_id.required' =>
            'Debe seleccionar un nivel educativo.',

            'date.required' =>
            'La fecha de la excepción es obligatoria.',

            'name.required' =>
            'Debe indicar el nombre de la excepción.',

            'schedule.name.required' =>
            'Debe indicar un nombre para el horario excepcional.',

            'schedule.events.min' =>
            'El horario excepcional debe tener al menos una entrada y una salida.',
        ];
    }
}
