<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\IndexEnrollmentRequest;
use App\Http\Requests\Enrollment\StoreEnrollmentRequest;
use App\Http\Requests\Enrollment\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnrollmentController extends Controller
{
    /**
     * Relaciones necesarias para representar una matrícula.
     */
    private const RELATIONS = [
        'student.person',
        'academicYear',
        'gradeSection.grade.educationalLevel',
        'gradeSection.section',
    ];

    /**
     * Listado general de matrículas.
     */
    public function index(IndexEnrollmentRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $query = Enrollment::query()
            ->with(self::RELATIONS);

        /*
        |--------------------------------------------------------------------------
        | Búsqueda de estudiante
        |--------------------------------------------------------------------------
        |
        | Permite buscar por:
        | - código de estudiante
        | - número de documento
        | - nombres
        | - apellido paterno
        | - apellido materno
        |
        */
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $terms = preg_split(
                '/\s+/',
                $search,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            $query->whereHas(
                'student',
                function ($studentQuery) use ($search, $terms) {
                    $studentQuery->where(
                        function ($query) use ($search, $terms) {
                            /*
                    |--------------------------------------------------------------------------
                    | Código
                    |--------------------------------------------------------------------------
                    */

                            $query->where(
                                'student_code',
                                'like',
                                "%{$search}%"
                            );

                            /*
                    |--------------------------------------------------------------------------
                    | Datos personales
                    |--------------------------------------------------------------------------
                    */

                            $query->orWhereHas(
                                'person',
                                function ($personQuery) use ($search, $terms) {
                                    $personQuery->where(
                                        function ($query) use ($search, $terms) {
                                            /*
                                    | Documento completo
                                    */

                                            $query->where(
                                                'document_number',
                                                'like',
                                                "%{$search}%"
                                            );

                                            /*
                                    | Cada término puede aparecer en cualquiera
                                    | de los campos del nombre.
                                    */

                                            $query->orWhere(
                                                function ($nameQuery) use ($terms) {
                                                    foreach ($terms as $term) {
                                                        $nameQuery->where(
                                                            function ($termQuery) use ($term) {
                                                                $termQuery
                                                                    ->where(
                                                                        'first_names',
                                                                        'like',
                                                                        "%{$term}%"
                                                                    )
                                                                    ->orWhere(
                                                                        'paternal_surname',
                                                                        'like',
                                                                        "%{$term}%"
                                                                    )
                                                                    ->orWhere(
                                                                        'maternal_surname',
                                                                        'like',
                                                                        "%{$term}%"
                                                                    );
                                                            }
                                                        );
                                                    }
                                                }
                                            );
                                        }
                                    );
                                }
                            );
                        }
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Año académico
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['academic_year_id'])) {
            $query->where(
                'academic_year_id',
                $filters['academic_year_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nivel educativo
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['educational_level_id'])) {
            $query->whereHas(
                'gradeSection.grade',
                function ($gradeQuery) use ($filters) {
                    $gradeQuery->where(
                        'educational_level_id',
                        $filters['educational_level_id']
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Grado
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['grade_id'])) {
            $query->whereHas(
                'gradeSection',
                function ($gradeSectionQuery) use ($filters) {
                    $gradeSectionQuery->where(
                        'grade_id',
                        $filters['grade_id']
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Aula
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['grade_section_id'])) {
            $query->where(
                'grade_section_id',
                $filters['grade_section_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Paginación
        |--------------------------------------------------------------------------
        */

        $perPage = $filters['per_page'] ?? 15;

        $enrollments = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Registrar una matrícula.
     */
    public function store(StoreEnrollmentRequest $request): EnrollmentResource
    {
        $data = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Estado por defecto
        |--------------------------------------------------------------------------
        */

        $data['status'] ??= EnrollmentStatus::Enrolled->value;

        $enrollment = Enrollment::create($data);

        $enrollment->load(self::RELATIONS);

        return new EnrollmentResource($enrollment);
    }

    /**
     * Mostrar una matrícula.
     */
    public function show(Enrollment $enrollment): EnrollmentResource
    {
        $enrollment->load(self::RELATIONS);

        return new EnrollmentResource($enrollment);
    }

    /**
     * Actualizar una matrícula.
     *
     * student_id y academic_year_id son inmutables porque
     * UpdateEnrollmentRequest no permite recibirlos.
     */
    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): EnrollmentResource
    {
        $enrollment->update(
            $request->validated()
        );

        $enrollment->load(self::RELATIONS);

        return new EnrollmentResource($enrollment);
    }

    /**
     * Historial académico de un estudiante.
     */
    public function studentHistory(Student $student): AnonymousResourceCollection
    {
        $enrollments = $student
            ->enrollments()
            ->with([
                'academicYear',
                'gradeSection.grade.educationalLevel',
                'gradeSection.section',
            ])
            ->orderByDesc('academic_year_id')
            ->orderByDesc('enrollment_date')
            ->get();

        return EnrollmentResource::collection($enrollments);
    }
}
