<x-layout>
    <x-slot:title>Sign In - Scoutline</x-slot:title>

    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    </x-slot>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Enter credentials or <a href="/signup">create an account</a></p>
            </div>

            @if (session('success'))
                <div class="success-alert">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="error-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="/login" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" required placeholder="name@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-primary">Sign In</button>
            </form>
        </div>
    </div>
</x-layout>