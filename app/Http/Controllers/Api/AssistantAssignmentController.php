<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssistantAssignment\IndexAssistantAssignmentRequest;
use App\Http\Requests\AssistantAssignment\StoreAssistantAssignmentRequest;
use App\Http\Resources\AssistantAssignmentResource;
use App\Models\AssistantAssignment;
use App\Services\AssistantAssignment\AssistantAssignmentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AssistantAssignmentController extends Controller
{
    public function index(
        IndexAssistantAssignmentRequest $request
    ): AnonymousResourceCollection {
        $filters = $request->validated();

        $query = AssistantAssignment::query()
            ->with([
                'employee.person',
                'employee.positions',
                'gradeSection.academicYear',
                'gradeSection.grade',
                'gradeSection.section',
            ]);

        if (isset($filters['academic_year_id'])) {
            $query->whereHas(
                'gradeSection',
                fn ($q) => $q->where(
                    'academic_year_id',
                    $filters['academic_year_id']
                )
            );
        }

        if (isset($filters['employee_id'])) {
            $query->where(
                'employee_id',
                $filters['employee_id']
            );
        }

        if (isset($filters['grade_section_id'])) {
            $query->where(
                'grade_section_id',
                $filters['grade_section_id']
            );
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        if (isset($filters['effective_on'])) {
            $query->effectiveOn(
                $filters['effective_on']
            );
        }

        return AssistantAssignmentResource::collection(
            $query
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->paginate($filters['per_page'] ?? 15)
                ->withQueryString()
        );
    }

    public function store(
        StoreAssistantAssignmentRequest $request,
        AssistantAssignmentService $service
    ) {
        $assignment = $service->create(
            $request->validated()
        );

        return (new AssistantAssignmentResource($assignment))
            ->additional([
                'message' =>
                    'Asignación de auxiliar registrada correctamente.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(
        AssistantAssignment $assistantAssignment
    ): AssistantAssignmentResource {
        return new AssistantAssignmentResource(
            $assistantAssignment->load([
                'employee.person',
                'employee.positions',
                'gradeSection.academicYear',
                'gradeSection.grade',
                'gradeSection.section',
            ])
        );
    }
}
