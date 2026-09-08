<?php

namespace App\Http\Requests\GradeSection;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeSectionRequest extends FormRequest
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
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'section_id' => [
                'required',
                'exists:sections,id',
                Rule::unique('grade_sections')->where(
                    fn($query) =>
                    $query->where('academic_year_id', $this->input('academic_year_id'))
                        ->where('grade_id', $this->input('grade_id'))
                ),
            ],
            'shift' => ['sometimes', Rule::in(['mañana', 'tarde', 'mañana y tarde'])],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
