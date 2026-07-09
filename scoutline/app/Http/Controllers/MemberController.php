<?php

namespace App\Http\Controllers;
use App\Models\Member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // <-- FIX 1: Imported the Auth facade

class MemberController extends Controller
{
    public function create()
    {
        return view('signup');
    }

    public function store(Request $request)
    {
        // 1. Validate the incoming form inputs
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:members,email',
            'password' => 'required|string|min:8|confirmed',
        ]);


        // 2. FIX 2: Save the user and capture it into the $user variable!
        $user = Member::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), 
        ]);


        // 4. Redirect to the dashboard page successfully
         return redirect('/login')->with('success', 'Registration successful! Please log in.');
    }
}