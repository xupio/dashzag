<section>
    <p class="muted" style="margin-bottom:1rem;">Update your account's profile information and email address.</p>

    <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="form-group">
            <label class="label" for="name">Name</label>
            <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="label" for="email">Email</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')<div class="error-text">{{ $message }}</div>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="alert alert-info" style="margin-top:.75rem;">
                    Your email address is unverified.
                    <button form="send-verification" class="btn btn-outline" style="margin-left:.5rem;">Re-send verification email</button>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <div class="alert alert-success">A new verification link has been sent to your email address.</div>
                @endif
            @endif
        </div>

        <div class="form-group">
            <label class="label d-block" for="is_email_visible">Email visibility</label>
            <input type="hidden" name="is_email_visible" value="0">
            <div class="form-check">
                <input id="is_email_visible" name="is_email_visible" type="checkbox" class="form-check-input" value="1" @checked(old('is_email_visible', $user->is_email_visible))>
                <label class="form-check-label" for="is_email_visible">Show my email across profile and dashboard pages</label>
            </div>
            <div class="muted" style="margin-top:.35rem;">Default is hidden. When hidden, other pages show "Email hidden" instead of your real address.</div>
            @error('is_email_visible')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div style="display:flex; align-items:center; gap:.65rem;">
            <button class="btn btn-primary" type="submit">Save</button>
            @if (session('status') === 'profile-updated')
                <span class="muted">Saved.</span>
            @endif
        </div>
    </form>
</section>

