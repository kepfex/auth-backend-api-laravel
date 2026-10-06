<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\GradeSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEnrollmentRequest extends FormRequest
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
            'grade_section_id' => [
                'sometimes',
                'integer',
                'exists:grade_sections,id',
            ],

            'enrollment_date' => [
                'sometimes',
                'date',
            ],

            'status' => [
                'sometimes',
                Rule::enum(EnrollmentStatus::class),
            ],

            'observations' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $enrollment = $this->route('enrollment');

                if (!$enrollment) {
                    return;
                }
                
                $academicYearId = $this->integer(
                    'academic_year_id',
                    $enrollment->academic_year_id
                );

                $academicYearId = $enrollment->academic_year_id;

                $gradeSectionId = $this->integer(
                    'grade_section_id',
                    $enrollment->grade_section_id
                );

                $gradeSection = GradeSection::query()
                    ->select([
                        'id',
                        'academic_year_id',
                        'is_active',
                    ])
                    ->find($gradeSectionId);

                if (!$gradeSection) {
                    return;
                }

                if (
                    $gradeSection->academic_year_id !==
                    $academicYearId
                ) {
                    $validator->errors()->add(
                        'grade_section_id',
                        'El aula seleccionada no pertenece al año académico indicado.'
                    );
                }

                if (!$gradeSection->is_active) {
                    $validator->errors()->add(
                        'grade_section_id',
                        'El aula seleccionada no se encuentra activa.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.exists' =>
            'El año académico seleccionado no existe.',

            'grade_section_id.exists' =>
            'El aula seleccionada no existe.',

            'enrollment_date.date' =>
            'La fecha de matrícula no es válida.',

            'observations.max' =>
            'Las observaciones no pueden superar los 2000 caracteres.',
        ];
    }
}
