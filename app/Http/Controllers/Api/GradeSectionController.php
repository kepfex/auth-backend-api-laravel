<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeSection\StoreGradeSectionRequest;
use App\Http\Requests\GradeSection\UpdateGradeSectionRequest;
use App\Http\Resources\GradeSectionResource;
use App\Models\GradeSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GradeSectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $gradeSections  = GradeSection::with(['academicYear', 'grade.educationalLevel', 'section'])
            ->paginate(15);
        
        return GradeSectionResource::collection($gradeSections);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGradeSectionRequest $request): GradeSectionResource
    {
        $gradeSection = GradeSection::create($request->validated());

        return new GradeSectionResource($gradeSection->load(['academicYear', 'grade', 'section']));
    }

    /**
     * Display the specified resource.
     */
    public function show(GradeSection $gradeSection): GradeSectionResource
    {
        return new GradeSectionResource($gradeSection->load(['academicYear', 'grade.educationalLevel', 'section']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGradeSectionRequest $request, GradeSection $gradeSection): GradeSectionResource
    {
        $gradeSection->update($request->validated());

        return new GradeSectionResource($gradeSection->load(['academicYear', 'grade', 'section']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GradeSection $gradeSection): Response
    {
        $gradeSection->delete();

        return response()->noContent();
    }
}
