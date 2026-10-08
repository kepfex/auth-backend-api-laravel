<?php

namespace App\Http\Requests\AttendanceCalendarException;

use App\Enums\AttendanceCalendarExceptionType;
use App\Services\Attendance\AttendanceCalendarExceptionValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceCalendarExceptionRequest extends FormRequest
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
                'nullable',
                'integer',
                'exists:educational_levels,id',
            ],

            'grade_section_id' => [
                'nullable',
                'integer',
                'exists:grade_sections,id',
            ],

            'attendance_schedule_id' => [
                'nullable',
                'integer',
                'exists:attendance_schedules,id',
            ],

            'date' => [
                'required',
                'date',
            ],

            'type' => [
                'required',
                Rule::enum(
                    AttendanceCalendarExceptionType::class
                ),
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
        ];
    }

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ) {
                app(
                    AttendanceCalendarExceptionValidationService::class
                )->validate(
                    $validator,
                    $this->all()
                );
            },
        ];
    }
}
