<x-layout>
    <section class="dashboard" style="max-width: 450px; margin: 80px auto; padding: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px;">
        <h2>Establish New Password</h2>
        <p style="color: #a1a1aa; font-size: 14px; margin-bottom: 20px;">Define your new secure master passphrase token entry credentials below.</p>

        @if ($errors->any())
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-size: 14px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="console">
            @csrf
            <input type="hidden" name="id" value="{{ $id }}">
            <input type="hidden" name="hash" value="{{ $hash }}">

            <div class="console-field">
                <label>New Access Passphrase</label>
                <input type="password" name="password" required placeholder="Minimum 8 characters">
            </div>
            <div class="console-field" style="margin-top: 15px;">
                <label>Confirm Access Passphrase</label>
                <input type="password" name="password_confirmation" required placeholder="Re-type passphrase">
            </div>
            <button type="submit" class="btn btn-signal" style="width: 100%; margin-top: 20px;">Update Account Access</button>
        </form>
    </section>
</x-layout>