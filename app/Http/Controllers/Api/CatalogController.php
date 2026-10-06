<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\GuardianRelationship;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function guardianRelationships(): JsonResponse
    {
        return response()->json([
            'data' => GuardianRelationship::options(),
        ]);
    }

    public function enrollmentStatuses()
    {
        return response()->json([
            'data' => EnrollmentStatus::options(),
        ]);
    }
}
