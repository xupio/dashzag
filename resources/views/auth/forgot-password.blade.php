<x-guest-layout>
    <h1 class="page-title" style="font-size:1.2rem; margin-bottom:1rem;">Forgot Password</h1>
    <p class="muted" style="margin-bottom:1rem;">Enter your email and we will send a password reset link.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label class="label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div style="text-align:right;">
            <button class="btn btn-primary" type="submit">Email Password Reset Link</button>
        </div>
    </form>
</x-guest-layout>
