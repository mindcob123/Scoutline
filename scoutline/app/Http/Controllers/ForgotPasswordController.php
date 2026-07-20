<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Mail\PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ForgotPasswordController extends Controller
{
     // Displays the forgot password request form.
    public function showRequestForm()
    {
        return view('forgot_password_request');
    }
     // Handles the request to send password reset link. Validates email, finds user (silently), generates secure signed URL and sends email.
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = Member::where('email', $request->email)->first();

        if ($user) {                                                 // Security practice: don't reveal if user doesn't exist, just confirm execution
            $resetUrl = URL::temporarySignedRoute(
                'password.reset',
                now()->addMinutes(15),                                // Tight expiration window for maximum security
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            Mail::to($user->email)->send(new PasswordResetNotification($user->name, $resetUrl));
        }
        return back()->with('success', 'If the email matches an active account, a recovery link has been dispatched.');
    }

     // Displays the password reset form after clicking the reset link. Validates the signed route parameters for security.
    public function showResetForm(Request $request, $id, $hash)
    {
        $user = Member::findOrFail($id);
        if (sha1($user->email) !== $hash) {                      // Final sanity check against email tampering
            abort(403, 'Invalid signature metadata.');
        }

        return view('forgot_password_reset', ['id' => $id, 'hash' => $hash]);
    }

    // Updates the user's password after form submission. Validates input, verifies hash, updates password and redirects to login.
    public function updatePassword(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:members,id',
            'hash' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Member::findOrFail($request->id);

        if (sha1($user->email) !== $request->hash) {
            return back()->withErrors(['email' => 'Signature verification failed.']);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect('/login')->with('success', 'Your password has been securely updated. Please log in.');
    }
}