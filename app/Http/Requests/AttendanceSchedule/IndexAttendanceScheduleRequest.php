<?php

namespace App\Http\Requests\AttendanceSchedule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceScheduleRequest extends FormRequest
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

            'is_active' => [
                'nullable',
                'boolean',
            ],

            /*
             * Buscar qué horario estaba
             * vigente en una fecha concreta.
             */
            'date' => [
                'nullable',
                'date',
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
