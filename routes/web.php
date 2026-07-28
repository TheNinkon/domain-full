<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\dashboard\Analytics;
use App\Http\Controllers\domains\DomainCategoryController;
use App\Http\Controllers\domains\DomainController;
use App\Http\Controllers\domains\DomainOfferController;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\projects\ProjectController;
use App\Http\Controllers\settings\CaptchaSettingController;
use App\Http\Controllers\settings\MailSettingController;

// locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');

// Authentication (public registration is disabled — private admin panel)
Route::middleware('guest')->group(function () {
    Route::get('/auth/login-basic', [LoginBasic::class, 'index'])->name('auth-login-basic');
    Route::post('/auth/login-basic', [LoginBasic::class, 'authenticate'])
        ->middleware('throttle:5,1')
        ->name('auth-login-basic.attempt');
});

Route::post('/logout', [LoginBasic::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Private admin panel
Route::middleware('auth')->group(function () {
    Route::get('/', [Analytics::class, 'index'])->name('dashboard-analytics');

    Route::prefix('domains')->name('domains.')->group(function () {
        Route::get('/', [DomainController::class, 'index'])->name('index');
        Route::get('/create', [DomainController::class, 'create'])->name('create');
        Route::post('/', [DomainController::class, 'store'])->name('store');

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [DomainCategoryController::class, 'index'])->name('index');
            Route::post('/', [DomainCategoryController::class, 'store'])->name('store');
            Route::put('/{category}', [DomainCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [DomainCategoryController::class, 'destroy'])->name('destroy');
        });

        Route::get('/{domain}', [DomainController::class, 'show'])->name('show');
        Route::get('/{domain}/edit', [DomainController::class, 'edit'])->name('edit');
        Route::put('/{domain}', [DomainController::class, 'update'])->name('update');
        Route::delete('/{domain}', [DomainController::class, 'destroy'])->name('destroy');
        Route::post('/{domain}/notes', [DomainController::class, 'storeNote'])->name('notes.store');
        Route::post('/{domain}/status', [DomainController::class, 'updateStatus'])->name('status.update');
        Route::post('/{domain}/renew', [DomainController::class, 'renew'])->name('renew');
        Route::post('/{domain}/offers/{offer}/accept', [DomainOfferController::class, 'accept'])->name('offers.accept');
        Route::post('/{domain}/offers/{offer}/reject', [DomainOfferController::class, 'reject'])->name('offers.reject');
    });

    Route::resource('projects', ProjectController::class);

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/mail', [MailSettingController::class, 'edit'])->name('mail.edit');
        Route::put('/mail', [MailSettingController::class, 'update'])->name('mail.update');
        Route::post('/mail/test', [MailSettingController::class, 'test'])->name('mail.test');

        Route::get('/captcha', [CaptchaSettingController::class, 'edit'])->name('captcha.edit');
        Route::put('/captcha', [CaptchaSettingController::class, 'update'])->name('captcha.update');
    });
});
