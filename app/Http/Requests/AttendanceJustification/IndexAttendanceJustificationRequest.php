<?php

namespace App\Http\Requests\AttendanceJustification;

use App\Enums\AttendanceJustificationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAttendanceJustificationRequest extends FormRequest
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
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'attendance_day_id' => [
                'nullable',
                'integer',
                'exists:attendance_days,id',
            ],

            'attendance_mark_id' => [
                'nullable',
                'integer',
                'exists:attendance_marks,id',
            ],

            'student_id' => [
                'nullable',
                'integer',
                'exists:students,id',
            ],

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

            'status' => [
                'nullable',
                Rule::enum(
                    AttendanceJustificationStatus::class
                ),
            ],

            'scope' => [
                'nullable',
                Rule::in([
                    'day',
                    'mark',
                ]),
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
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
