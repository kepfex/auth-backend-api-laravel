<?php

namespace App\Http\Requests\AttendanceCalendarException;

use App\Enums\AttendanceCalendarExceptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAttendanceCalendarExceptionRequest extends FormRequest
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
                'nullable',
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

            'date' => [
                'nullable',
                'date',
            ],

            'type' => [
                'nullable',
                Rule::enum(
                    AttendanceCalendarExceptionType::class
                ),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ];
    }
}
