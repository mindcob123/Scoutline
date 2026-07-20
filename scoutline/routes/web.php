<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LeadScanController;
use App\Http\Controllers\EnrichmentController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ProfileController;

// GUEST ROUTES (Only accessible when NOT logged in)
Route::middleware('guest')->group(function () {
    Route::get('/signup', [MemberController::class, 'create']);
    Route::post('/signup', [MemberController::class, 'store']);
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    // Password recovery
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{id}/{hash}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset')->middleware('signed');
    Route::post('/reset-password', [ForgotPasswordController::class, 'updatePassword'])->name('password.update');
});

// AUTHENTICATED ROUTES (Only accessible when logged in)
Route::middleware(['auth', \App\Http\Middleware\PreventBackHistory::class])->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'));
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    // Tools that require a user session
    Route::post('/scan', [LeadScanController::class, 'scan'])->name('scan');
    Route::get('/history', [LeadScanController::class, 'history'])->name('history.index');//search history
    Route::post('/business/enrich', [EnrichmentController::class, 'enrich'])->name('api.enrich');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Delete button
    Route::delete('/lead-scan/business/{businessId}', [LeadScanController::class, 'destroyBusiness'])->name('business.destroy');
});

// PUBLIC/STANDALONE ROUTES
Route::get('/', fn() => view('landing'));
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');
