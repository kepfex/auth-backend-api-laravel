<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return SectionResource::collection(Section::orderBy('name')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): SectionResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:sections,name'],
        ]);

        return new SectionResource(Section::create($validated));
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section): SectionResource
    {
        return new SectionResource($section);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Section $section): SectionResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:sections,name,' . $section->id],
        ]);

        $section->update($validated);

        return new SectionResource($section);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Section $section): Response|JsonResponse
    {

        if ($section->gradeSections()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la sección.',
                'code' => 'SECTION_IN_USE',
                'details' => 'La sección está asociada a uno o más grados.',
            ], Response::HTTP_CONFLICT);
        }

        $section->delete();

        return response()->noContent();
    }
}
