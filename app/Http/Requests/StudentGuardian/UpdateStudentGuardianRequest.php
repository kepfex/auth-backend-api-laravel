<?php

namespace App\Http\Requests\StudentGuardian;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentGuardianRequest extends FormRequest
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
            'relationship' => [
                'sometimes',
                'required',
                Rule::in([
                    'father',
                    'mother',
                    'grandfather',
                    'grandmother',
                    'uncle',
                    'aunt',
                    'sibling',
                    'legal_guardian',
                    'other',
                ]),
            ],

            'is_primary' => [
                'sometimes',
                'boolean',
            ],

            'receives_notifications' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
