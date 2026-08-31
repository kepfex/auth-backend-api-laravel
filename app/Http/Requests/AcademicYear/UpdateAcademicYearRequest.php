<?php

namespace App\Http\Requests\AcademicYear;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearRequest extends FormRequest
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
        $yearId = $this->route('academic_year')->id;

        return [
            'name'       => ['sometimes', 'string', 'max:4', Rule::unique('academic_years', 'name')->ignore($yearId)],
            'start_date' => ['sometimes', 'date'],
            'end_date'   => ['sometimes', 'date', 'after:start_date'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }
}
