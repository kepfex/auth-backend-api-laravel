<?php

namespace App\Http\Controllers\Api;

use App\Enums\GuardianRelationship;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function guardianRelationships(): JsonResponse
    {
        return response()->json([
            'data' => GuardianRelationship::options(),
        ]);
    }
}
