<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYear\StoreAcademicYearRequest;
use App\Http\Requests\AcademicYear\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = min(
            max((int) $request->integer('per_page', 10), 1),
            100
        );

        $academicYears = AcademicYear::query()
            ->orderByDesc('name')
            ->paginate($perPage);

        return AcademicYearResource::collection($academicYears);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAcademicYearRequest $request)
    {
        $academicYear = AcademicYear::create($request->validated());

        return new AcademicYearResource($academicYear);
    }

    /**
     * Display the specified resource.
     */
    public function show(AcademicYear $academicYear): AcademicYearResource
    {
        return new AcademicYearResource($academicYear);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): AcademicYearResource
    {
        $validated = $request->validated();

        DB::transaction(function () use (
            $validated,
            $academicYear
        ) {
            if (
                array_key_exists('is_active', $validated) &&
                $validated['is_active']
            ) {
                AcademicYear::query()
                    ->whereKeyNot($academicYear->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                    ]);
            }

            $academicYear->update($validated);
        });
        return new AcademicYearResource($academicYear->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicYear $academicYear): Response
    {
        $academicYear->delete();

        return response()->noContent();
    }
}
