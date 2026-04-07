<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiDocumentController;
use App\Http\Controllers\Api\ApiWorkflowController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class, 'login']);

Route::middleware('api.token')->group(function () {
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    Route::get('/dashboard', [ApiDocumentController::class, 'dashboard']);

    Route::prefix('documents')->group(function () {
        Route::get('/', [ApiDocumentController::class, 'index']);
        Route::get('/{document}', [ApiDocumentController::class, 'show']);
        Route::post('/', [ApiDocumentController::class, 'store']);
        Route::put('/{document}', [ApiDocumentController::class, 'update']);
        Route::get('/{document}/download', [ApiDocumentController::class, 'download']);
        Route::get('/{document}/timeline', [ApiDocumentController::class, 'timeline']);

        // Workflow
        Route::put('/{document}/submit', [ApiWorkflowController::class, 'submit']);
        Route::put('/{document}/validate', [ApiWorkflowController::class, 'validate']);
        Route::put('/{document}/reject', [ApiWorkflowController::class, 'reject']);
        Route::put('/{document}/approve', [ApiWorkflowController::class, 'approve']);
        Route::post('/{document}/sign', [ApiWorkflowController::class, 'sign']);
    });
});
