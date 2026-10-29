<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreInstitutionalUserRequest;
use App\Http\Resources\InstitutionalUserResource;
use App\Services\User\InstitutionalUserRegistrationService;
use Illuminate\Http\JsonResponse;

class InstitutionalUserController extends Controller
{
    public function store(
        StoreInstitutionalUserRequest $request,
        InstitutionalUserRegistrationService $registration
    ): JsonResponse {
        $data = $request->validated();
        $user = $registration->create($data['person_id'], $data['profile_type'], $data['password']);

        return (new InstitutionalUserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
