<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\SaveInstitutionSettingRequest;
use App\Http\Resources\InstitutionSettingResource;
use App\Models\InstitutionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InstitutionSettingController extends Controller
{
    public function show(): InstitutionSettingResource|JsonResponse
    {
        $institution = InstitutionSetting::find(InstitutionSetting::SINGLETON_ID);

        if (!$institution) {
            return response()->json(['data' => null]);
        }

        return new InstitutionSettingResource($institution);
    }

    public function update(SaveInstitutionSettingRequest $request): InstitutionSettingResource
    {
        $data = $request->validated();

        $institution = DB::transaction(function () use ($data) {
            // Una instalación = una institución. ID fijo y único.
            // La segunda solicitud concurrente actualiza la fila existente.
            return InstitutionSetting::query()->updateOrCreate(
                ['id' => InstitutionSetting::SINGLETON_ID],
                $data
            );
        });

        return new InstitutionSettingResource($institution->refresh());
    }
}
