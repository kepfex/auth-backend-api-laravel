<?php

namespace App\Http\Requests\GradeSection;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeSectionRequest extends FormRequest
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
        $gradeSection = $this->route('grade_section');
        $academicYearId = $this->input('academic_year_id', $gradeSection->academic_year_id);
        $gradeId = $this->input('grade_id', $gradeSection->grade_id);

        return [
            'academic_year_id' => ['sometimes', 'exists:academic_years,id'],
            'grade_id' => ['sometimes', 'exists:grades,id'],
            'section_id' => [
                'sometimes',
                'exists:sections,id',
                Rule::unique('grade_sections')
                    ->where(
                        fn($query) => $query
                            ->where('academic_year_id', $academicYearId)
                            ->where('grade_id', $gradeId)
                            ->where('section_id', $this->section_id)
                            ->where('shift', $this->shift)
                    )
                    ->ignore($gradeSection->id),
            ],
            'shift' => ['sometimes', Rule::in(['mañana', 'tarde', 'mañana y tarde'])],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
