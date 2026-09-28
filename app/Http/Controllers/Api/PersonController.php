<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Person\StorePersonRequest;
use App\Http\Resources\PersonResource;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'document_type' => ['required_with:document_number', Rule::in(['DNI', 'CE', 'PASSPORT'])],
            'document_number' => ['required_with:document_type', 'string', 'max:20'],
        ]);
        if (!isset($filters['document_number'])) return response()->json(['message' => 'Se requiere documento para buscar una persona.'], 422);

        $person = Person::with('student')
            ->where('document_type', $filters['document_type'])
            ->where('document_number', strtoupper(trim($filters['document_number'])))
            ->first();

        return response()->json(['data' => $person ? new PersonResource($person) : null]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonRequest $request)
    {
        return (new PersonResource(Person::create($request->validated())))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Person $person): PersonResource
    {
        return new PersonResource($person->load('student'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Person $person)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Person $person)
    {
        //
    }
}
