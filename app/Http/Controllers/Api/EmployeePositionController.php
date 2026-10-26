<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\SyncEmployeePositionsRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\Employee\EmployeePositionService;

class EmployeePositionController extends Controller
{
    public function update(
        SyncEmployeePositionsRequest $request,
        Employee $employee,
        EmployeePositionService $service
    ): EmployeeResource {
        $employee = $service->sync(
            $employee,
            $request->validated('position_ids')
        );

        return (new EmployeeResource($employee))
            ->additional([
                'message' =>
                'Cargos del empleado actualizados correctamente.',
            ]);
    }
}
