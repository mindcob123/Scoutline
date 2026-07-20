<x-layout>
    <x-slot:title>Sign Up - Scoutline</x-slot:title>
    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('css/signup.css') }}">
    </x-slot:styles>
    <!-- SIGNUP PAGE -->
    <div class="auth-wrapper">
        <div class="auth-card">

            <!-- Signup Header -->
            <div class="auth-header">
                <h2>Create Account</h2>
                <p>Join Scoutline or <a href="/login">sign in to your dashboard</a></p>
            </div>

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="error-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Signup Form -->
            <form action="/signup" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" 
                           value="{{ old('name') }}" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" 
                           value="{{ old('email') }}" required placeholder="name@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" 
                           required placeholder="••••••••">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-input" 
                           required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-primary">Register Account</button>
            </form>

        </div>
    </div>
</x-layout>