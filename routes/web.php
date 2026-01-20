<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FonnteWebhookController;
use App\Http\Controllers\WebReportController;

Route::get('/', [WebReportController::class, 'index'])->name('home');

// Web Reporting Routes
Route::get('/lapor', [WebReportController::class, 'create'])->name('report.create');
Route::post('/lapor', [WebReportController::class, 'store'])->name('report.store');
Route::get('/tracking', [WebReportController::class, 'trackingView'])->name('report.tracking.view');

Route::match(['get', 'post'], '/webhook/fonnte', [FonnteWebhookController::class, 'handle'])->name('webhook.fonnte');
