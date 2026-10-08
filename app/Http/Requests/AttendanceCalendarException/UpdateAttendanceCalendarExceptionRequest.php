<?php

namespace App\Http\Requests\AttendanceCalendarException;

use App\Models\AttendanceCalendarException;
use App\Services\Attendance\AttendanceCalendarExceptionValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAttendanceCalendarExceptionRequest extends FormRequest
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
                'prohibited',
            ],

            'educational_level_id' => [
                'prohibited',
            ],

            'grade_section_id' => [
                'prohibited',
            ],

            'attendance_schedule_id' => [
                'prohibited',
            ],

            'date' => [
                'prohibited',
            ],

            'type' => [
                'prohibited',
            ],

            'name' => [
                'sometimes',
                'string',
                'max:150',
            ],

            'reason' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ) {
                /** @var AttendanceCalendarException|null $exception */
                $exception =
                    $this->route(
                        'attendance_calendar_exception'
                    );

                if (!$exception) {
                    return;
                }

                app(
                    AttendanceCalendarExceptionValidationService::class
                )->validate(
                    $validator,
                    $this->all(),
                    $exception
                );
            },
        ];
    }
}
