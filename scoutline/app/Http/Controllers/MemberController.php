<?php
namespace App\Http\Controllers;
use App\Mail\EmailVerificationNotification;
use Illuminate\Support\Facades\Mail;       
use Illuminate\Support\Facades\URL;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;  

 //---------SIGN UP-------------
class MemberController extends Controller
{
    public function create()
    {
        return view('signup');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([                                   //Validate the incoming form inputs
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:members,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Member::create([                                          //Create a new member record in the database with the validated data
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), 
        ]);
        $temporarySignedUrl = URL::temporarySignedRoute(                  //Generate a unique, cryptographically signed URL valid for 60 minutes
            'verification.verify',now()->addMinutes(60),                  // Link automatically expires in 1 hour
            [
                'id' => $user->id, 
                'hash' => sha1($user->email)                              // Protects against tampering
            ]
        );
        Mail::to($user->email)->send(new EmailVerificationNotification($user->name, $temporarySignedUrl)); //Dispatch your custom notification email
        return redirect('/login')->with('success', 'Registration successful! Please log in.');   //Redirect to the dashboard page successfully
    }
}