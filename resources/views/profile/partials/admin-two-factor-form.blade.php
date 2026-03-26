@php($setup = $adminTwoFactorSetup ?? null)

@if (session('status') === 'admin-two-factor-setup-created')
    <div class="alert alert-success">Authenticator setup created. Scan the QR code and confirm with a 6-digit code.</div>
@elseif (session('status') === 'admin-two-factor-enabled')
    <div class="alert alert-success">Admin two-factor authentication is now enabled.</div>
@elseif (session('status') === 'admin-two-factor-disabled')
    <div class="alert alert-success">Admin two-factor authentication has been disabled.</div>
@endif

@if (! $user->hasAdminTwoFactorEnabled() && ! $user->hasPendingAdminTwoFactorSetup())
    <p class="text-muted mb-3">Protect the admin account with an authenticator app challenge after password login.</p>

    <form method="POST" action="{{ route('profile.admin-two-factor.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="admin_two_factor_current_password_create">Current password</label>
            <input id="admin_two_factor_current_password_create" class="form-control" type="password" name="current_password" required>
            @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <button class="btn btn-primary" type="submit">Generate authenticator setup</button>
    </form>
@elseif ($user->hasPendingAdminTwoFactorSetup() && $setup)
    <p class="text-muted mb-3">Scan this QR code with your authenticator app, then enter the current 6-digit code to finish setup.</p>

    @if ($setup['qr'] ?? null)
        <div class="mb-3 text-center">
            <img src="{{ $setup['qr'] }}" alt="Admin 2FA QR code" style="max-width: 240px; width: 100%;">
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label">Manual setup key</label>
        <input class="form-control" type="text" readonly value="{{ $setup['secret'] }}">
    </div>

    <form method="POST" action="{{ route('profile.admin-two-factor.confirm') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="admin_two_factor_current_password_confirm">Current password</label>
            <input id="admin_two_factor_current_password_confirm" class="form-control" type="password" name="current_password" required>
            @error('current_password', 'adminTwoFactor')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="admin_two_factor_code">Authenticator code</label>
            <input id="admin_two_factor_code" class="form-control" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required>
            @error('code', 'adminTwoFactor')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" type="submit">Enable admin 2FA</button>
        </div>
    </form>
@else
    <p class="text-muted mb-3">Admin 2FA is enabled and required after password login.</p>

    <div class="mb-3 small text-muted">
        Enabled on {{ optional($user->admin_two_factor_confirmed_at)->format('M j, Y g:i A') }}
    </div>

    <form method="POST" action="{{ route('profile.admin-two-factor.destroy') }}">
        @csrf
        @method('DELETE')

        <div class="mb-3">
            <label class="form-label" for="admin_two_factor_current_password_delete">Current password</label>
            <input id="admin_two_factor_current_password_delete" class="form-control" type="password" name="current_password" required>
            @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <button class="btn btn-outline-danger" type="submit">Disable admin 2FA</button>
    </form>
@endif
