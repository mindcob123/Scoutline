<x-layout>
    {{-- Import the CSS file --}}
    <link rel="stylesheet" href="{{ asset('css/forgetpassreset.css') }}">

    <section class="auth-container">
        <h2>Create New Password</h2>
        <p style="color: #a1a1aa; font-size: 14px; margin-bottom: 25px;">Define your new password credentials below.</p>

        @if ($errors->any())
            <div class="error-box">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{ $id }}">
            <input type="hidden" name="hash" value="{{ $hash }}">

            <div class="console-field">
                <label style="color: #d1d5db; font-size: 13px;">New Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="console-field" style="margin-top: 15px;">
                <label style="color: #d1d5db; font-size: 13px;">Confirm Password</label>
                <input type="password" name="password_confirmation" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-signal">
                Update Account Access
            </button>
        </form>
    </section>
</x-layout>