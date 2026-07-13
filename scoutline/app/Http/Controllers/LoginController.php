<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()                                                    // show the login form
    {
        return view('login');
    }

    public function store(Request $request)                                   // handle the login submission
    {
        $attributes = $request->validate([                                    // Validate inputs
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (! Auth::attempt($attributes)) {                                   // Attempt to log the user in using the credentials
            throw ValidationException::withMessages([
                'email' => 'Sorry, those credentials do not match our records.'
            ]);
        }
        $request->session()->regenerate();                                   // Regenerate session to protect against session fixation attacks
        return redirect('/dashboard')->with('success', 'Welcome back!');     // Redirect straight to dashboard
    }

    public function destroy(Request $request)                                  // Handle logging out
    {
        Auth::logout();                                                       // Log the user out
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'Logged out successfully.'); // Redirect to login page with a success message
    }
}