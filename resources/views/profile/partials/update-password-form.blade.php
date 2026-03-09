<section>
    <p class="muted" style="margin-bottom:1rem;">Use a long, random password to keep your account secure.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="form-group">
            <label class="label" for="update_password_current_password">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password">
            @if ($errors->updatePassword->has('current_password'))
                <div class="error-text">{{ $errors->updatePassword->first('current_password') }}</div>
            @endif
        </div>

        <div class="form-group">
            <label class="label" for="update_password_password">New Password</label>
            <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password">
            @if ($errors->updatePassword->has('password'))
                <div class="error-text">{{ $errors->updatePassword->first('password') }}</div>
            @endif
        </div>

        <div class="form-group">
            <label class="label" for="update_password_password_confirmation">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password">
            @if ($errors->updatePassword->has('password_confirmation'))
                <div class="error-text">{{ $errors->updatePassword->first('password_confirmation') }}</div>
            @endif
        </div>

        <div style="display:flex; align-items:center; gap:.65rem;">
            <button class="btn btn-primary" type="submit">Save</button>
            @if (session('status') === 'password-updated')
                <span class="muted">Saved.</span>
            @endif
        </div>
    </form>
</section>
