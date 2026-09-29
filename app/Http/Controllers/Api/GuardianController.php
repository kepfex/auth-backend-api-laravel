<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\StoreGuardianRequest;
use App\Http\Requests\Guardian\UpdateGuardianRequest;
use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuardianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGuardianRequest $request)
    {
        $input = $request->validated();

        $guardian = DB::transaction(function () use ($input) {

            $person = isset($input['person_id'])
                ? Person::query()->findOrFail($input['person_id'])
                : Person::create($input['person']);

            return Guardian::create([
                'person_id' => $person->id,
                'occupation' => $input['occupation'] ?? null,
                'is_active' => $input['is_active'] ?? true,
            ]);
        });

        return (new GuardianResource(
            $guardian->load('person')
        ))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Guardian $guardian): GuardianResource
    {
        return new GuardianResource($guardian->load('person'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGuardianRequest $request, Guardian $guardian): GuardianResource
    {
        $guardian->update($request->validated());

        return new GuardianResource(
            $guardian->load('person')
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
