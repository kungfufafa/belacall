<?php

use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Auth\MagicLinkLoginController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\WebReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebReportController::class, 'index'])->name('home');

// Legal Pages
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');

// Web Reporting Routes
Route::get('/lapor', [WebReportController::class, 'create'])->name('report.create');
Route::post('/lapor', [WebReportController::class, 'store'])->name('report.store');
Route::get('/tracking', [WebReportController::class, 'trackingView'])->name('report.tracking.view');
Route::post('/tracking/verify-phone', [WebReportController::class, 'verifyTrackingPhone'])->name('report.tracking.verify_phone');
Route::post('/tracking/revision', [WebReportController::class, 'submitRevision'])->name('report.tracking.revision');

Route::get('/auth/magic-link/{user}', MagicLinkLoginController::class)
    ->middleware('signed')
    ->name('auth.magic-link.login');

Route::get('/webhook/telegram', fn () => response('OK', 200));
Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle'])
    ->middleware('throttle:telegram-webhook')
    ->name('webhook.telegram');
