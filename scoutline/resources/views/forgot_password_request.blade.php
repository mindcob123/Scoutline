<x-layout>
    <x-slot:styles>
       <link rel="stylesheet" href="{{ asset('css/global.css') }}">
       <link rel="stylesheet" href="{{ asset('css/forgetpassrequest.css') }}">
    </x-slot:styles>
    <!-- PASSWORD RESET REQUEST PAGE -->
    <section class="auth-card">

        <!-- Page Header -->
        <h2 class="auth-header">Account Recovery</h2>
        <p class="auth-subheader">Enter your registration email address to receive a secure link.</p>
        
        <!-- Success Message -->
        @if(session('success'))
            <div class="success-alert">
                {{ session('success') }}
            </div>
        @endif

        <!-- Error Message -->
        @if ($errors->any())
            <div class="error-alert">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Password Reset Request Form -->
        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Your Email</label>
                <input type="email" name="email" class="form-input" 
                       required placeholder="name@domain.com">
            </div>
            
            <button type="submit" class="btn-primary">
                Send Recovery Link
            </button>
        </form>

    </section>
</x-layout>