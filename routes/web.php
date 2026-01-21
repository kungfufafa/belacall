<?php

use App\Http\Controllers\Api\FonnteWebhookController;
use App\Http\Controllers\Api\GowaWebhookController;
use App\Http\Controllers\WebReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebReportController::class, 'index'])->name('home');

// Web Reporting Routes
Route::get('/lapor', [WebReportController::class, 'create'])->name('report.create');
Route::post('/lapor', [WebReportController::class, 'store'])->name('report.store');
Route::get('/tracking', [WebReportController::class, 'trackingView'])->name('report.tracking.view');
Route::post('/tracking/revision', [WebReportController::class, 'submitRevision'])->name('report.tracking.revision');

Route::match(['get', 'post'], '/webhook/fonnte', [FonnteWebhookController::class, 'handle'])->name('webhook.fonnte');
Route::match(['get', 'post'], '/webhook/gowa', [GowaWebhookController::class, 'handle'])->name('webhook.gowa');
