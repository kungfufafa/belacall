<?php

use App\Http\Controllers\Api\FonnteWebhookController;
use App\Http\Controllers\WebReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebReportController::class, 'index'])->name('home');

// Web Reporting Routes
Route::get('/lapor', [WebReportController::class, 'create'])->name('report.create');
Route::post('/lapor', [WebReportController::class, 'store'])->name('report.store');
Route::get('/tracking', [WebReportController::class, 'trackingView'])->name('report.tracking.view');
Route::post('/tracking/request-otp', [WebReportController::class, 'requestTrackingOtp'])->name('report.tracking.request_otp');
Route::post('/tracking/verify-otp', [WebReportController::class, 'verifyTrackingOtp'])->name('report.tracking.verify_otp');
Route::post('/tracking/revision', [WebReportController::class, 'submitRevision'])->name('report.tracking.revision');

Route::get('/webhook/fonnte', fn () => response('OK', 200));
Route::post('/webhook/fonnte', [FonnteWebhookController::class, 'handle'])
    ->middleware('throttle:fonnte-webhook')
    ->name('webhook.fonnte');
