<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
     // Handles the edit profile request. Redirects back as the form is shown in a drawer.
    public function edit()
    {
        return redirect()->back();
        }

    // Updates the authenticated user's profile information. 
    // Validates name, email and current password. Help updating password and then returns JSON response for frontend.
    public function update(Request $request)
    {
        $user = $request->user();
        // validating the user's input.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:members,email,' . $user->id],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        // encrypting the new password that the user will give.
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        // sending the response back to frontend with a success msg and not refreshing the whole page.
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'new_name' => $user->name,
            'new_email' => $user->email
        ]);
    }
}