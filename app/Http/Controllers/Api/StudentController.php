<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\IndexStudentRequest;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Person;
use App\Models\Student;
use App\Services\Qr\QrCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexStudentRequest $request)
    {
        $filters = $request->validated();
        $search = trim($filters['search'] ?? '');

        $students = Student::query()
            ->with(['person', 'qrCard:id,student_id,is_active'])
            ->when(
                isset($filters['status']),
                fn($q) => $q->where('status', $filters['status'])
            )
            ->when($search !== '', fn($q) =>
            $q->where(function ($inner) use ($search) {
                $inner
                    ->where('student_code', 'like', '%' . $search . '%')
                    ->orWhereHas('person', fn($person) =>
                    $person->where(function ($names) use ($search) {
                        $names
                            ->where('document_number', 'like', '%' . $search . '%')
                            ->orWhere('first_names', 'like', '%' . $search . '%')
                            ->orWhere('paternal_surname', 'like', '%' . $search . '%')
                            ->orWhere('maternal_surname', 'like', '%' . $search . '%');
                    }));
            }))
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return StudentResource::collection($students);
    }

    /**
     * Almacena un estudiante recién creado junto con su código QR.
     */
    public function store(StoreStudentRequest $request, QrCardService $qrCardService)
    {
        $input = $request->validated();

        $student = DB::transaction(function () use ($input, $qrCardService) {

            // 1. Obtener o registrar la persona.
            $person = isset($input['person_id'])
                ? Person::query()->findOrFail($input['person_id'])
                : Person::create($input['person']);

            // 2. Registrar al estudiante.
            $student = Student::create([
                'person_id' => $person->id,
                'student_code' => $input['student_code'],
                'status' => $input['status'] ?? 'activo',
            ]);

            // 3. Generar automáticamente su UUID.
            $qrCardService->createForStudent($student);

            return $student;
        });

        // 4. Responder con el estudiante registrado.
        return (new StudentResource($student->load(['person', 'qrCard'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): StudentResource
    {
        return new StudentResource($student->load('person'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateStudentRequest $request,
        Student $student
    ) {
        $validated =
            $request->validated();

        $student = DB::transaction(
            function () use (
                $request,
                $validated,
                $student
            ) {
                if (
                    isset(
                        $validated['person']
                    )
                ) {
                    $student->person->update(
                        $validated['person']
                    );
                }

                $student->update(
                    $request->safe()->only([
                        'student_code',
                        'status',
                    ])
                );

                return $student
                    ->fresh()
                    ->load('person');
            }
        );

        return response()->json([
            'data' =>
            new StudentResource(
                $student
            ),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        //
    }
}
