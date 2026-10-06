<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\GradeSection;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEnrollmentRequest extends FormRequest
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
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'academic_year_id' => [
                'required',
                'integer',
                'exists:academic_years,id',
            ],

            'grade_section_id' => [
                'required',
                'integer',
                'exists:grade_sections,id',
            ],

            'enrollment_date' => [
                'required',
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

                $gradeSection = GradeSection::query()
                    ->select([
                        'id',
                        'academic_year_id',
                        'is_active',
                    ])
                    ->find($this->integer('grade_section_id'));

                if (!$gradeSection) {
                    return;
                }

                if (
                    $gradeSection->academic_year_id !==
                    $this->integer('academic_year_id')
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

                $alreadyEnrolled = Enrollment::query()
                    ->where('student_id', $this->integer('student_id'))
                    ->where(
                        'academic_year_id',
                        $this->integer('academic_year_id')
                    )
                    ->exists();

                if ($alreadyEnrolled) {
                    $validator->errors()->add(
                        'student_id',
                        'El estudiante ya cuenta con una matrícula para el año académico seleccionado.'
                    );
                }

                $student = Student::query()
                    ->select([
                        'id',
                        'status',
                    ])
                    ->find($this->integer('student_id'));

                if (
                    $student &&
                    !in_array($student->status, ['activo'], true)
                ) {
                    $validator->errors()->add(
                        'student_id',
                        'El estudiante no se encuentra habilitado para una nueva matrícula.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' =>
            'Debe seleccionar un estudiante.',

            'student_id.exists' =>
            'El estudiante seleccionado no existe.',

            'academic_year_id.required' =>
            'Debe seleccionar un año académico.',

            'academic_year_id.exists' =>
            'El año académico seleccionado no existe.',

            'grade_section_id.required' =>
            'Debe seleccionar un aula.',

            'grade_section_id.exists' =>
            'El aula seleccionada no existe.',

            'enrollment_date.required' =>
            'La fecha de matrícula es obligatoria.',

            'enrollment_date.date' =>
            'La fecha de matrícula no es válida.',

            'observations.max' =>
            'Las observaciones no pueden superar los 2000 caracteres.',
        ];
    }
}
