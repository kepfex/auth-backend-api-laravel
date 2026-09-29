<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentGuardian\StoreStudentGuardianRequest;
use App\Http\Requests\StudentGuardian\UpdateStudentGuardianRequest;
use App\Http\Resources\StudentGuardianResource;
use App\Models\Student;
use App\Models\StudentGuardian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentGuardianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Student $student): AnonymousResourceCollection
    {
        $guardians = $student->studentGuardians()
            ->with('guardian.person')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return StudentGuardianResource::collection($guardians);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentGuardianRequest $request, Student $student)
    {
        $input = $request->validated();

        $alreadyLinked = StudentGuardian::query()
            ->where('student_id', $student->id)
            ->where('guardian_id', $input['guardian_id'])
            ->exists();

        if ($alreadyLinked) {
            throw ValidationException::withMessages([
                'guardian_id' =>
                'Este apoderado ya está vinculado al estudiante.',
            ]);
        }

        $studentGuardian = DB::transaction(
            function () use ($student, $input) {

                if ($input['is_primary'] ?? false) {
                    StudentGuardian::query()
                        ->where('student_id', $student->id)
                        ->where('is_primary', true)
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                return StudentGuardian::create([
                    'student_id' => $student->id,
                    'guardian_id' => $input['guardian_id'],
                    'relationship' => $input['relationship'],
                    'is_primary' => $input['is_primary'] ?? false,
                    'receives_notifications' =>
                    $input['receives_notifications'] ?? true,
                ]);
            }
        );

        return (new StudentGuardianResource(
            $studentGuardian->load('guardian.person')
        ))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentGuardianRequest $request, Student $student, StudentGuardian $studentGuardian): StudentGuardianResource
    {
        $this->ensureBelongsToStudent(
            $student,
            $studentGuardian
        );

        $input = $request->validated();

        DB::transaction(function () use (
            $student,
            $studentGuardian,
            $input
        ) {
            if (
                array_key_exists('is_primary', $input)
                && $input['is_primary'] === true
            ) {
                StudentGuardian::query()
                    ->where('student_id', $student->id)
                    ->where('id', '!=', $studentGuardian->id)
                    ->where('is_primary', true)
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            $studentGuardian->update($input);
        });

        return new StudentGuardianResource(
            $studentGuardian
                ->refresh()
                ->load('guardian.person')
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student, StudentGuardian $studentGuardian)
    {
        $this->ensureBelongsToStudent(
            $student,
            $studentGuardian
        );

        $studentGuardian->delete();

        return response()->noContent();
    }

    private function ensureBelongsToStudent(
        Student $student,
        StudentGuardian $studentGuardian
    ): void {
        abort_unless(
            $studentGuardian->student_id === $student->id,
            404
        );
    }
}
