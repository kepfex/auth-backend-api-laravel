<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Grade\StoreGradeRequest;
use App\Http\Requests\Grade\UpdateGradeRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $grades = Grade::with('educationalLevel')
            ->orderBy('educational_level_id')
            ->orderBy('order')
            ->get();

        return GradeResource::collection($grades);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGradeRequest $request): GradeResource
    {
        $grade = Grade::create($request->validated());

        return new GradeResource($grade->load('educationalLevel'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Grade $grade)
    {
        return new GradeResource($grade->load('educationalLevel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGradeRequest $request, Grade $grade): GradeResource
    {
        $grade->update($request->validated());

        return new GradeResource($grade->load('educationalLevel'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grade $grade): Response|JsonResponse
    {
        if ($grade->gradeSections()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el grado.',
                'code' => 'GRADE_IN_USE',
                'details' => 'El grado está siendo utilizado en una o más aulas o secciones del año académico.',
            ], Response::HTTP_CONFLICT);
        }

        $grade->delete();

        return response()->noContent();
    }
}
