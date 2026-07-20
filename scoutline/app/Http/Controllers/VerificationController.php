<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
     // Handles email verification from the signed link. 
     // Validates the hash, marks the email as verified if not already, and redirects to login.
    public function verify(Request $request, $id, $hash)
    {
        $user = Member::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->email))) {
            abort(403, 'Invalid verification link.');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect('/login')->with('success', 'Email verified successfully!');
    }
}