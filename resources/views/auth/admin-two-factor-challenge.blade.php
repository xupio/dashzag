<x-guest-layout>
    <h1 class="page-title" style="font-size:1.2rem; margin-bottom:1rem;">Admin Verification</h1>

    <p class="text-muted mb-4">
        Enter the 6-digit code from your authenticator app to finish signing in
        @if ($pendingUser)
            for <strong>{{ strtolower($pendingUser->email) }}</strong>.
        @endif
    </p>

    <form method="POST" action="{{ route('admin.two-factor.verify') }}">
        @csrf

        <div class="form-group">
            <label class="label" for="code">Authenticator code</label>
            <input id="code" class="form-control" type="text" name="code" value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus>
            @error('code')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div style="display:flex; justify-content:flex-end; align-items:center; gap:.75rem;">
            <button class="btn btn-primary" type="submit">Verify</button>
        </div>
    </form>
</x-guest-layout>
