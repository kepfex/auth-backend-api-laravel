<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\GradeSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CredentialPrintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id'),
            ],
            'grade_section_id' => [
                'required',
                'integer',
                Rule::exists('grade_sections', 'id'),
            ],
        ]);

        // Comprobar que el aula pertenece al año solicitado.
        $classroom = GradeSection::query()
            ->with(['grade.educationalLevel', 'section'])
            ->whereKey($data['grade_section_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->firstOrFail();

        $enrollments = Enrollment::query()
            ->with([
                'student.person',
                'student.qrCard',
            ])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('grade_section_id', $classroom->id)
            ->where('status', 'matriculado')
            ->orderBy('student_id')
            ->get();

        $items = $enrollments->map(function ($enrollment) {
            $student = $enrollment->student;
            $person = $student->person;
            $qrCard = $student->qrCard;

            return [
                'enrollment_id' => $enrollment->id,
                'student_id' => $student->id,
                'student_code' => $student->student_code,
                'student_status' => $student->status,

                'full_name' => trim(implode(' ', [
                    $person->paternal_surname,
                    $person->maternal_surname,
                    $person->first_names,
                ])),

                'qr' => $qrCard ? [
                    'uuid' => $qrCard->uuid,
                    'is_active' => $qrCard->is_active,
                ] : null,
            ];
        });

        return response()->json([
            'data' => [
                'academic_year_id' => (int) $data['academic_year_id'],
                'grade_section_id' => $classroom->id,
                'grade' => $classroom->grade->name,
                'section' => $classroom->section->name,
                'level' => $classroom->grade->educationalLevel->name,
                'students' => $items->values(),
            ],
        ]);
    }
}
