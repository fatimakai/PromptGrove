<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\PromptAnalysisApiController;
use App\Http\Controllers\Api\PromptApiController;
use App\Http\Controllers\Api\PublicPromptApiController;
use App\Http\Controllers\BillingWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:api-login');

Route::get('/public/prompts', [PublicPromptApiController::class, 'index']);
Route::get('/public/prompts/{prompt:slug}', [PublicPromptApiController::class, 'show']);
Route::post('/webhooks/razorpay', [BillingWebhookController::class, 'razorpay'])->name('webhooks.razorpay');
Route::post('/webhooks/paypal', [BillingWebhookController::class, 'paypal'])->name('webhooks.paypal');
Route::post('/webhooks/stripe', [BillingWebhookController::class, 'stripe'])->name('webhooks.stripe');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::get('/prompts/{prompt}/analyses', [PromptAnalysisApiController::class, 'index'])->name('api.prompts.analyses.index');
    Route::post('/prompts/{prompt}/analyses', [PromptAnalysisApiController::class, 'store'])
        ->middleware('throttle:prompt-analysis-api')->name('api.prompts.analyses.store');
    Route::get('/prompts/{prompt}/analyses/{analysis}', [PromptAnalysisApiController::class, 'show'])->name('api.prompts.analyses.show');
    Route::apiResource('prompts', PromptApiController::class)
        ->parameters(['prompts' => 'prompt'])
        ->names('api.prompts');
});
