<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\IndexEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\Employee\EmployeeRegistrationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    /**
     * Listar empleados con filtros y paginación.
     */
    public function index(
        IndexEmployeeRequest $request
    ): AnonymousResourceCollection {
        $filters = $request->validated();

        $query = Employee::query()
            ->with([
                'person',
                'positions',
            ]);

        // Búsqueda por código, documento o nombres.
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'employee_code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas('person', function ($personQuery) use ($search) {
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
                    });
            });
        }

        // Filtrar empleados activos/inactivos.
        if (array_key_exists('is_active', $filters)) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        // Filtrar empleados que tengan un cargo.
        if (!empty($filters['position_id'])) {
            $query->whereHas(
                'positions',
                fn($positionQuery) =>
                $positionQuery->whereKey(
                    $filters['position_id']
                )
            );
        }

        return EmployeeResource::collection(
            $query
                ->orderByDesc('id')
                ->paginate($filters['per_page'] ?? 15)
                ->withQueryString()
        );
    }

    /**
     * Registrar un empleado.
     */
    public function store(
        StoreEmployeeRequest $request,
        EmployeeRegistrationService $registrationService
    ) {
        $employee = $registrationService->register(
            $request->validated()
        );

        return (new EmployeeResource($employee))
            ->additional([
                'message' => 'Empleado registrado correctamente.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Consultar el detalle de un empleado.
     */
    public function show(
        Employee $employee
    ): EmployeeResource {
        return new EmployeeResource(
            $employee->load([
                'person',
                'positions',
            ])
        );
    }
}
