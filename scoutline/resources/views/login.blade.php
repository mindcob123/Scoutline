<x-layout>
    <x-slot:title>Sign In - Scoutline</x-slot:title>

    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    </x-slot:styles>
    <!-- LOGIN PAGE -->
    <div class="auth-wrapper">
        <div class="auth-card">

            <!-- Login Header -->
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Enter credentials or <a href="/signup">create an account</a></p>
            </div>

            <!-- Success Message -->
            @if (session('success'))
                <div class="success-alert">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="error-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Login Form -->
            <form action="/login" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" 
                           required placeholder="name@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" 
                           required placeholder="••••••••">
                    
                    <!-- Forgot Password Link -->
                    <div style="margin-top: 8px; text-align: right;">
                        <a href="{{ route('password.request') }}" style="color: #ef4444; font-size: 13px; text-decoration: none;">
                            Forgot Password?
                        </a>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Sign In</button>
            </form>

        </div>
    </div>
</x-layout>