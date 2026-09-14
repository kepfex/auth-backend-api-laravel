<?php

namespace App\Http\Requests\GradeSection;

use Illuminate\Foundation\Http\FormRequest;

class IndexGradeSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'sometimes',
                'integer',
                'exists:academic_years,id',
            ],

            'educational_level_id' => [
                'sometimes',
                'integer',
                'exists:educational_levels,id',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
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
