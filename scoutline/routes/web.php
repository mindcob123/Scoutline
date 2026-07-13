<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LeadScanController;
use App\Http\Controllers\EnrichmentController;
use App\Http\Controllers\ForgotPasswordController;


Route::get('/', function () {                                                              //LANDING PAGE
    return view('landing');
});

Route::get('/signup', [MemberController::class, 'create']);                                 //REGISTRATION SYSTEM 
Route::post('/signup', [MemberController::class, 'store']);


Route::get('/dashboard', function () {                                                       //DASHBOARD protected by auth middleware, only accessible to authenticated users 

    return view('dashboard');
})->middleware('auth');


Route::get('/login', [LoginController::class, 'create'])->name('login');                       //LOGIN & LOGOUT SYSTEM
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::post('/scan', [LeadScanController::class, 'scan'])->name('scan');                        //LEAD SCAN SYSTEM

Route::post('/business/enrich', [EnrichmentController::class, 'enrich'])->name('api.enrich');   // LEAD ENRICHMENT SYSTEM

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {                                //HANDLES THE CLICKED EMAIL LINK
    $user = Member::findOrFail($id);                                                          //looks up that specific user in your members database table
    if (!hash_equals((string) $hash, sha1($user->email))) {                                   // Validate if the email hash matches what was signed
        abort(403, 'Invalid verification link.');
    }
    if (!$user->hasVerifiedEmail()) {                                                        
        $user->markEmailAsVerified(); 
    }
    return redirect('/login')->with('success', 'Email verified successfully! You can now log in.');
})->middleware(['signed'])->name('verification.verify');                                     // 'signed' middleware verifies that the URL hasn't been tampered with or expired!


Route::get('/forgot-password', [ForgotPasswordController::class, 'showRequestForm'])->name('password.request'); // Request Reset Form & Email Processor
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

Route::get('/reset-password/{id}/{hash}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset')->middleware('signed');// Secure Signed Password Entry Forms & Update Handlers
Route::post('/reset-password', [ForgotPasswordController::class, 'updatePassword'])->name('password.update');