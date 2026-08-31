<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYear\StoreAcademicYearRequest;
use App\Http\Requests\AcademicYear\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academicYears = AcademicYear::query()
        ->orderByDesc('start_date')
        ->paginate(10);

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
        $academicYear->update($request->validated());

        return new AcademicYearResource($academicYear);   
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
