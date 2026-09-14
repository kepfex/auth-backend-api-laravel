<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeSection\IndexGradeSectionRequest;
use App\Http\Requests\GradeSection\StoreGradeSectionRequest;
use App\Http\Requests\GradeSection\UpdateGradeSectionRequest;
use App\Http\Resources\GradeSectionResource;
use App\Models\EducationalLevel;
use App\Models\Grade;
use App\Models\GradeSection;
use App\Models\Section;
use Illuminate\Http\Response;

class GradeSectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexGradeSectionRequest $request)
    {
        $validated = $request->validated();

        $gradeSections = GradeSection::query()
            ->with([
                'academicYear',
                'grade.educationalLevel',
                'section',
            ])

            ->when(
                isset($validated['academic_year_id']),
                fn($query) => $query->where(
                    'academic_year_id',
                    $validated['academic_year_id']
                )
            )

            ->when(
                isset($validated['educational_level_id']),
                fn($query) => $query->whereHas(
                    'grade',
                    fn($gradeQuery) =>
                    $gradeQuery->where(
                        'educational_level_id',
                        $validated['educational_level_id']
                    )
                )
            )

            ->when(
                array_key_exists('is_active', $validated),
                fn($query) => $query->where(
                    'is_active',
                    $validated['is_active']
                )
            )

            ->orderBy(
                EducationalLevel::select('educational_levels.order')
                    ->join(
                        'grades',
                        'grades.educational_level_id',
                        '=',
                        'educational_levels.id'
                    )
                    ->whereColumn(
                        'grades.id',
                        'grade_sections.grade_id'
                    )
            )
            ->orderBy(
                Grade::select('grades.order')
                    ->whereColumn(
                        'grades.id',
                        'grade_sections.grade_id'
                    )
            )
            ->orderBy(
                Section::select('sections.name')
                    ->whereColumn(
                        'sections.id',
                        'grade_sections.section_id'
                    )
            )
            ->paginate($validated['per_page'] ?? 15);

        return GradeSectionResource::collection(
            $gradeSections
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGradeSectionRequest $request): GradeSectionResource
    {
        $gradeSection = GradeSection::create(
            $request->validated()
        );

        return new GradeSectionResource(
            $gradeSection->load([
                'academicYear',
                'grade.educationalLevel',
                'section'
            ])
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(GradeSection $gradeSection): GradeSectionResource
    {
        return new GradeSectionResource(
            $gradeSection->load([
                'academicYear',
                'grade.educationalLevel',
                'section'
            ])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGradeSectionRequest $request, GradeSection $gradeSection): GradeSectionResource
    {
        $gradeSection->update($request->validated());

        return new GradeSectionResource(
            $gradeSection->load([
                'academicYear',
                'grade.educationalLevel',
                'section'
            ])
        );
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
