<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AssistantAssignmentController;
use App\Http\Controllers\Api\AttendanceCalendarExceptionController;
use App\Http\Controllers\Api\AttendanceDayController;
use App\Http\Controllers\Api\AttendanceJustificationController;
use App\Http\Controllers\Api\AttendanceMarkController;
use App\Http\Controllers\Api\AttendanceScanController;
use App\Http\Controllers\Api\AttendanceScheduleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CredentialPrintController;
use App\Http\Controllers\Api\EducationalLevelController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeePositionController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\GradeSectionController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\InstitutionSettingController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\Api\QrCardController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentGuardianController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get(
        'institution-settings',
        [InstitutionSettingController::class, 'show']
    );

    Route::put(
        'institution-settings',
        [InstitutionSettingController::class, 'update']
    );
    Route::prefix('catalogs')->group(function () {
        Route::get(
            'guardian-relationships',
            [CatalogController::class, 'guardianRelationships']
        );
        Route::get(
            'enrollment-statuses',
            [CatalogController::class, 'enrollmentStatuses']
        );
        Route::get(
            'attendance-schedule-event-types',
            [CatalogController::class, 'attendanceScheduleEventTypes']
        );

        Route::get(
            'weekdays',
            [CatalogController::class, 'weekdays']
        );

        Route::get(
            'attendance-day-statuses',
            [CatalogController::class, 'attendanceDayStatuses',]
        );

        Route::get(
            'attendance-mark-statuses',
            [CatalogController::class, 'attendanceMarkStatuses',]
        );

        Route::get(
            'attendance-mark-sources',
            [CatalogController::class, 'attendanceMarkSources',]
        );

        Route::get(
            'attendance-justification-statuses',
            [CatalogController::class, 'attendanceJustificationStatuses',]
        );

        Route::get(
            'attendance-calendar-exception-types',
            [CatalogController::class, 'attendanceCalendarExceptionTypes',]
        );

        Route::get(
            'attendance-schedule-types',
            [CatalogController::class, 'attendanceScheduleTypes',]
        );
    });

    Route::apiResource('academic-years', AcademicYearController::class);
    Route::apiResource('educational-levels', EducationalLevelController::class);
    Route::apiResource('grades', GradeController::class);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('sections', SectionController::class);
    Route::apiResource('grade-sections', GradeSectionController::class);
    Route::apiResource('persons', PersonController::class);
    Route::apiResource('students', StudentController::class);

    // Apoderados
    Route::apiResource('guardians', GuardianController::class)
        ->only([
            'store',
            'show',
            'update',
        ]);

    Route::prefix('students/{student}')
        ->group(function () {

            Route::get(
                'guardians',
                [StudentGuardianController::class, 'index']
            );

            Route::post(
                'guardians',
                [StudentGuardianController::class, 'store']
            );

            Route::patch(
                'guardians/{studentGuardian}',
                [StudentGuardianController::class, 'update']
            );

            Route::delete(
                'guardians/{studentGuardian}',
                [StudentGuardianController::class, 'destroy']
            );
        });

    // Gestión de empleados
    Route::apiResource('employees', EmployeeController::class)
        ->only([
            'index',
            'store',
            'show',
        ]);

    // Actualizar cargos institucionales.
    Route::put(
        'employees/{employee}/positions',
        [EmployeePositionController::class, 'update']
    );

    // Activar o desactivar empleado.
    Route::patch(
        'employees/{employee}/status',
        [EmployeeController::class, 'updateStatus']
    );

    // ============================================
    // ASIGNACIONES DE AUXILIARES
    // ============================================

    Route::apiResource(
        'assistant-assignments',
        AssistantAssignmentController::class
    )->only([
        'index',
        'store',
        'show',
    ]);

    Route::patch(
        'assistant-assignments/{assistantAssignment}/finish',
        [AssistantAssignmentController::class, 'finish']
    );

    // Ruta para matriculas
    Route::apiResource('enrollments', EnrollmentController::class)
        ->only([
            'index',
            'store',
            'show',
            'update',
        ]);

    // Historial de matriculas de un estudiante 
    Route::get(
        'students/{student}/enrollments',
        [EnrollmentController::class, 'studentHistory']
    );

    // Rutas para horarios de asistencia
    Route::apiResource(
        'attendance-schedules',
        AttendanceScheduleController::class
    )->only(['index', 'store', 'show', 'update',]);

    // Rutas para asistencia diaria
    Route::apiResource(
        'attendance-days',
        AttendanceDayController::class
    )->only(['index', 'show',]);

    // Rutas para marcas de asistencia
    Route::post(
        'attendance-marks/manual',
        [
            AttendanceMarkController::class,
            'storeManual',
        ]
    );

    // Rutas para justificaciones de asistencia
    Route::get(
        'attendance-justifications',
        [AttendanceJustificationController::class, 'index',]
    );

    Route::post(
        'attendance-justifications',
        [AttendanceJustificationController::class, 'store',]
    );

    Route::get(
        'attendance-justifications/{attendance_justification}',
        [AttendanceJustificationController::class, 'show',]
    );

    Route::patch(
        'attendance-justifications/{attendance_justification}/review',
        [AttendanceJustificationController::class, 'review',]
    );

    Route::get(
        'attendance-justifications/{attendance_justification}/attachment',
        [AttendanceJustificationController::class, 'attachment',]
    );

    /*
    |--------------------------------------------------------------------------
    | Códigos QR de estudiantes
    |--------------------------------------------------------------------------
    |
    | Estas rutas permiten consultar y actualizar su estado.
    |
    */
    Route::prefix('students/{student}/qr-card')->group(function () {
        Route::get(
            '/',
            [QrCardController::class, 'current']
        );

        Route::patch(
            'status',
            [QrCardController::class, 'updateStatus']
        );
    });

    // Esta ruta consulta las credenciales de un aula
    Route::get(
        'credential-print/students',
        [CredentialPrintController::class, 'index']
    );

    // Rutas para excepciones del calendario de asistencia
    Route::apiResource(
        'attendance-calendar-exceptions',
        AttendanceCalendarExceptionController::class
    )->only(['index', 'store', 'show', 'update',]);

    // Ruta para excepciones del calendario de asistencia que anulan el horario de asistencia para un día específico.
    Route::post(
        'attendance-calendar-exceptions/schedule-override',
        [AttendanceCalendarExceptionController::class, 'storeScheduleOverride',]
    );
});

// Ruta para escaneo de QR de asistencia
Route::post('attendance/scan', [AttendanceScanController::class, 'store',])
    ->middleware('throttle:attendance-scan');
