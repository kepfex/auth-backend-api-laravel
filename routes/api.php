<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EducationalLevelController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\GradeSectionController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\SectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('academic-years', AcademicYearController::class);
    Route::apiResource('educational-levels', EducationalLevelController::class);
    Route::apiResource('grades', GradeController::class);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('sections', SectionController::class);
    Route::apiResource('grade-sections', GradeSectionController::class);
});