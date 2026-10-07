<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceSchedule\IndexAttendanceScheduleRequest;
use App\Http\Requests\AttendanceSchedule\StoreAttendanceScheduleRequest;
use App\Http\Requests\AttendanceSchedule\UpdateAttendanceScheduleRequest;
use App\Http\Resources\AttendanceScheduleResource;
use App\Models\AttendanceSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AttendanceScheduleController extends Controller
{
    private const RELATIONS = [
        'academicYear',
        'educationalLevel',
        'gradeSection.academicYear',
        'gradeSection.grade.educationalLevel',
        'gradeSection.section',
        'events',
    ];

    /**
     * Listado de horarios.
     */
    public function index(
        IndexAttendanceScheduleRequest $request
    ): AnonymousResourceCollection {
        $filters =
            $request->validated();

        $query =
            AttendanceSchedule::query()
            ->with(self::RELATIONS);

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

        if (
            isset(
                $filters['educational_level_id']
            )
        ) {
            $query->where(
                'educational_level_id',
                $filters['educational_level_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Aula específica
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['grade_section_id']
            )
        ) {
            $query->where(
                'grade_section_id',
                $filters['grade_section_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Activo / inactivo
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists(
                'is_active',
                $filters
            )
        ) {
            $query->where(
                'is_active',
                $filters['is_active']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Vigencia para una fecha
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['date']
            )
        ) {
            $query
                ->whereDate(
                    'valid_from',
                    '<=',
                    $filters['date']
                )
                ->whereDate(
                    'valid_until',
                    '>=',
                    $filters['date']
                );
        }

        $schedules =
            $query
            ->orderByDesc(
                'valid_from'
            )
            ->orderBy('name')
            ->paginate(
                $filters['per_page']
                    ?? 15
            )
            ->withQueryString();

        return AttendanceScheduleResource::collection(
            $schedules
        );
    }

    /**
     * Crear horario completo.
     */
    public function store(
        StoreAttendanceScheduleRequest $request
    ): AttendanceScheduleResource {
        $data =
            $request->validated();

        $events =
            $data['events'];

        unset(
            $data['events']
        );

        $data['is_active'] ??= true;

        $schedule =
            DB::transaction(
                function () use (
                    $data,
                    $events
                ) {
                    $schedule =
                        AttendanceSchedule::create(
                            $data
                        );

                    $schedule
                        ->events()
                        ->createMany(
                            $events
                        );

                    return $schedule;
                }
            );

        $schedule->load(
            self::RELATIONS
        );

        return new AttendanceScheduleResource(
            $schedule
        );
    }

    /**
     * Ver horario.
     */
    public function show(
        AttendanceSchedule $attendanceSchedule
    ): AttendanceScheduleResource {
        $attendanceSchedule->load(
            self::RELATIONS
        );

        return new AttendanceScheduleResource(
            $attendanceSchedule
        );
    }

    /**
     * Actualizar horario.
     */
    public function update(
        UpdateAttendanceScheduleRequest $request,
        AttendanceSchedule $attendanceSchedule
    ): AttendanceScheduleResource {
        $data =
            $request->validated();

        $hasEvents =
            array_key_exists(
                'events',
                $data
            );

        $events =
            $hasEvents
            ? $data['events']
            : [];

        unset(
            $data['events']
        );

        DB::transaction(
            function () use (
                $attendanceSchedule,
                $data,
                $events,
                $hasEvents
            ) {
                if (!empty($data)) {
                    $attendanceSchedule
                        ->update($data);
                }

                /*
                |--------------------------------------------------------------------------
                | Reemplazar configuración de eventos
                |--------------------------------------------------------------------------
                |
                | En esta Fase 7A todavía no existen AttendanceMarks.
                |
                | Cuando lleguemos a 7B protegeremos los horarios que ya tengan
                | asistencia histórica para evitar alterar el pasado.
                |
                */

                if ($hasEvents) {
                    $attendanceSchedule
                        ->events()
                        ->delete();

                    $attendanceSchedule
                        ->events()
                        ->createMany(
                            $events
                        );
                }
            }
        );

        $attendanceSchedule->load(
            self::RELATIONS
        );

        return new AttendanceScheduleResource(
            $attendanceSchedule
        );
    }
}
