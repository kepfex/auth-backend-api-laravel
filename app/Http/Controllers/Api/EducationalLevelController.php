<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EducationalLevelResource;
use App\Models\EducationalLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EducationalLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $levels = EducationalLevel::with('grades')
            ->orderBy('order')
            ->get();

        return EducationalLevelResource::collection($levels);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): EducationalLevelResource
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:educational_levels,code'],
            'name' => ['required', 'string', 'max:50', 'unique:educational_levels,name'],
            'order' => ['required', 'integer', 'unique:educational_levels,order'],
        ]);

        $level = EducationalLevel::create($validated);

        return new EducationalLevelResource($level);
    }

    /**
     * Display the specified resource.
     */
    public function show(EducationalLevel $educationalLevel): EducationalLevelResource
    {
        return new EducationalLevelResource($educationalLevel->load('grades'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EducationalLevel $educationalLevel): EducationalLevelResource
    {
        $validated = $request->validate([
            'code' => ['sometimes',  'string', 'max:10', 'unique:educational_levels,code,' . $educationalLevel->id,],
            'name'  => ['sometimes', 'string', 'max:50', 'unique:educational_levels,name,' . $educationalLevel->id],
            'order' => ['sometimes', 'integer', 'unique:educational_levels,order,' . $educationalLevel->id],
        ]);

        $educationalLevel->update($validated);

        return new EducationalLevelResource($educationalLevel);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EducationalLevel $educationalLevel): Response
    {
        $educationalLevel->delete();

        return response()->noContent();
    }
}
