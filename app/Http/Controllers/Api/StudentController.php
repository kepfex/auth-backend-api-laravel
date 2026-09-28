<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\IndexStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Person;
use App\Models\Student;
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
        $students = Student::query()->with('person')
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when($search !== '', fn($q) => $q->where(function ($inner) use ($search) {
                $inner->where('student_code', 'like', '%' . $search . '%')
                    ->orWhereHas('person', fn($person) => $person->where(function ($names) use ($search) {
                        $names->where('document_number', 'like', '%' . $search . '%')
                            ->orWhere('first_names', 'like', '%' . $search . '%')
                            ->orWhere('paternal_surname', 'like', '%' . $search . '%')
                            ->orWhere('maternal_surname', 'like', '%' . $search . '%');
                    }));
            }))
            ->orderByDesc('id')->paginate($filters['per_page'] ?? 15)->withQueryString();

        return StudentResource::collection($students);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->validated();
        $student = DB::transaction(function () use ($input) {
            $person = isset($input['person_id'])
                ? Person::query()->findOrFail($input['person_id'])
                : Person::create($input['person']);
            return Student::create([
                'person_id' => $person->id,
                'student_code' => $input['student_code'],
                'status' => $input['status'] ?? 'active',
            ]);
        });

        return (new StudentResource($student->load('person')))->response()->setStatusCode(201);
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
    public function update(Request $request, Student $student): StudentResource
    {
        $input = $request->validate([
            'student_code' => ['sometimes', 'required', 'string', 'max:25', Rule::unique('students', 'student_code')->ignore($student->id)],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);
        if (isset($input['student_code'])) $input['student_code'] = strtoupper(trim($input['student_code']));
        $student->update($input);
        return new StudentResource($student->load('person'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        //
    }
}
