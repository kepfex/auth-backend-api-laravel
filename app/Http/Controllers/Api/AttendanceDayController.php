<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\IndexAttendanceDayRequest;
use App\Http\Resources\AttendanceDayResource;
use App\Models\AttendanceDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceDayController extends Controller
{
    private const RELATIONS = [
        'enrollment.student.person',
        'enrollment.academicYear',
        'enrollment.gradeSection.grade.educationalLevel',
        'enrollment.gradeSection.section',

        'schedule.events',

        'marks.scheduleEvent',
    ];

    public function index(
        IndexAttendanceDayRequest $request
    ): AnonymousResourceCollection {
        $filters =
            $request->validated();

        $query =
            AttendanceDay::query()
                ->with(
                    self::RELATIONS
                );

        /*
        |--------------------------------------------------------------------------
        | Búsqueda de estudiante
        |--------------------------------------------------------------------------
        */

        if (!empty(
            $filters['search']
        )) {
            $search =
                trim(
                    $filters['search']
                );

            $query->whereHas(
                'enrollment.student',
                function ($studentQuery) use ($search) {
                    $studentQuery
                        ->where(
                            'student_code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'person',
                            function ($personQuery) use ($search) {
                                $personQuery
                                    ->where(
                                        'document_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'first_names',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'paternal_surname',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'maternal_surname',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Estudiante
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['student_id']
            )
        ) {
            $query->whereHas(
                'enrollment',
                fn ($enrollmentQuery) =>
                    $enrollmentQuery->where(
                        'student_id',
                        $filters['student_id']
                    )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Año académico
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['academic_year_id']
            )
        ) {
            $query->whereHas(
                'enrollment',
                fn ($enrollmentQuery) =>
                    $enrollmentQuery->where(
                        'academic_year_id',
                        $filters[
                            'academic_year_id'
                        ]
                    )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nivel educativo
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters[
                    'educational_level_id'
                ]
            )
        ) {
            $query->whereHas(
                'enrollment.gradeSection.grade',
                fn ($gradeQuery) =>
                    $gradeQuery->where(
                        'educational_level_id',
                        $filters[
                            'educational_level_id'
                        ]
                    )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Grado
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['grade_id']
            )
        ) {
            $query->whereHas(
                'enrollment.gradeSection',
                fn ($gradeSectionQuery) =>
                    $gradeSectionQuery->where(
                        'grade_id',
                        $filters['grade_id']
                    )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Aula
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters[
                    'grade_section_id'
                ]
            )
        ) {
            $query->whereHas(
                'enrollment',
                fn ($enrollmentQuery) =>
                    $enrollmentQuery->where(
                        'grade_section_id',
                        $filters[
                            'grade_section_id'
                        ]
                    )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fecha exacta
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['date']
            )
        ) {
            $query->whereDate(
                'date',
                $filters['date']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Rango de fechas
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['date_from']
            )
        ) {
            $query->whereDate(
                'date',
                '>=',
                $filters['date_from']
            );
        }

        if (
            isset(
                $filters['date_to']
            )
        ) {
            $query->whereDate(
                'date',
                '<=',
                $filters['date_to']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['status']
            )
        ) {
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

        $attendanceDays =
            $query
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(
                    $filters['per_page']
                    ?? 15
                )
                ->withQueryString();

        return AttendanceDayResource::collection(
            $attendanceDays
        );
    }

    public function show(
        AttendanceDay $attendanceDay
    ): AttendanceDayResource {
        $attendanceDay->load(
            self::RELATIONS
        );

        return new AttendanceDayResource(
            $attendanceDay
        );
    }
}
