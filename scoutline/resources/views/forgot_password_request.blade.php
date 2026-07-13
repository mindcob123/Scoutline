<x-layout>
    <section class="dashboard" style="max-width: 450px; margin: 80px auto; padding: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px;">
        <h2>Account Recovery</h2>
        <p style="color: #a1a1aa; font-size: 14px; margin-bottom: 20px;">Enter your registration email address to receive a secure configuration token link.</p>
        
        @if(session('success'))
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-size: 14px;">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" class="console">
            @csrf
            <div class="console-field">
                <label>Operator Email</label>
                <input type="email" name="email" required placeholder="name@domain.com">
            </div>
            <button type="submit" class="btn btn-signal" style="width: 100%; margin-top: 15px;">Send Recovery Link</button>
        </form>
    </section>
</x-layout>