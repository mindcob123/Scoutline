<x-layout>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/forgetpassrequest.css') }}">

    <section class="auth-card">
        <h2 class="auth-header">Account Recovery</h2>
        <p class="auth-subheader">Enter your registration email address to receive a secure link.</p>
        
        @if(session('success'))
            <div class="success-alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error-alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Your Email</label>
                <input type="email" name="email" class="form-input" required placeholder="name@domain.com">
            </div>
            
            <button type="submit" class="btn-primary">
                Send Recovery Link
            </button>
        </form>
    </section>
</x-layout>