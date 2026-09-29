<?php

namespace App\Http\Requests\StudentGuardian;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentGuardianRequest extends FormRequest
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
            'guardian_id' => [
                'required',
                'integer',
                Rule::exists('guardians', 'id')
                    ->whereNull('deleted_at'),
            ],

            'relationship' => [
                'required',
                Rule::in([
                    'padre',
                    'madre',
                    'abuelo',
                    'abuela',
                    'tío',
                    'tía',
                    'hermano/a',
                    'tutor_legal',
                    'otro',
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
