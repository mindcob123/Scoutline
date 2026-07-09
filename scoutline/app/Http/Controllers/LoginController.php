<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // 1. Show the login form
    public function create()
    {
        return view('login');
    }

    // 2. Handle the login submission
    public function store(Request $request)
    {
        // Validate inputs
        $attributes = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Attempt to log the user in using the credentials
        if (! Auth::attempt($attributes)) {
            throw ValidationException::withMessages([
                'email' => 'Sorry, those credentials do not match our records.'
            ]);
        }

        // Regenerate session to protect against session fixation attacks
        $request->session()->regenerate();

        // Redirect straight to dashboard
        return redirect('/dashboard')->with('success', 'Welcome back!');
    }

    // 3. Handle logging out
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully.');
    }
}