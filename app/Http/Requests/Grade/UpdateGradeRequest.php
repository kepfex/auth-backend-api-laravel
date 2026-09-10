<?php

namespace App\Http\Requests\Grade;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
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
        $grade = $this->route('grade');
        $levelId = $this->input('educational_level_id', $grade->educational_level_id);

        return [
            'educational_level_id' => ['sometimes', 'exists:educational_levels,id'],
            'name' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('grades')
                    ->where(fn ($query) => $query->where('educational_level_id', $levelId))
                    ->ignore($grade->id),
            ],
            'order' => [
                'sometimes',
                'integer',
                'min:1',
                Rule::unique('grades')
                    ->where(fn ($query) => $query->where('educational_level_id', $levelId))
                    ->ignore($grade->id),
            ],
        ];
    }
}
