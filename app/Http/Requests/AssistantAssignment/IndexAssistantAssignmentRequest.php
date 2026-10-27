<?php

namespace App\Http\Requests\AssistantAssignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssistantAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'sometimes',
                'integer',
                Rule::exists('academic_years', 'id'),
            ],

            'educational_level_id' => [
                'sometimes',
                'integer',
                Rule::exists('educational_levels', 'id'),
            ],

            'employee_id' => [
                'sometimes',
                'integer',
                Rule::exists('employees', 'id'),
            ],

            'grade_section_id' => [
                'sometimes',
                'integer',
                Rule::exists('grade_sections', 'id'),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'effective_on' => [
                'sometimes',
                'date_format:Y-m-d',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
