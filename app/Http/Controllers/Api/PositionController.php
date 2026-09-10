<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PositionResource::collection(Position::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): PositionResource
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:positions,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        return new PositionResource(Position::create($validated));
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position): PositionResource
    {
        return new PositionResource($position);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position): PositionResource
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100', 'unique:positions,name,' . $position->id],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $position->update($validated);

        return new PositionResource($position);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): Response
    {
        $position->delete();

        return response()->noContent();
    }
}
