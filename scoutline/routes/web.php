<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LeadScanController;
use App\Http\Controllers\EnrichmentController;

Route::get('/', function () {
    return view('landing');
});

// --- REGISTRATION SYSTEM ---

Route::get('/signup', [MemberController::class, 'create']);
Route::post('/signup', [MemberController::class, 'store']);

// --- DASHBOARD (PROTECTED) ---

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');

// --- LOGIN & LOGOUT SYSTEM ---

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// --- LEAD SCAN SYSTEM ---
// Full page submit — Python (Geoapify) service is called here, results land in session.
Route::post('/scan', [LeadScanController::class, 'scan'])
    ->middleware('auth')
    ->name('scan');

// --- LEAD ENRICHMENT SYSTEM (AJAX, no page reload) ---
// Called by the "Fetch Leads" button per row. Takes a business's
// name + website and returns Apollo contact data as JSON.
Route::post('/api/enrich', [EnrichmentController::class, 'enrich'])
    ->middleware('auth')
    ->name('api.enrich');