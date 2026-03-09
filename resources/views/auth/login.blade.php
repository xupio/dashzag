<x-guest-layout>
    <h1 class="page-title" style="font-size:1.2rem; margin-bottom:1rem;">Sign In</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label class="label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="label" for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group form-check">
            <input id="remember_me" type="checkbox" name="remember">
            <label for="remember_me">Remember me</label>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; gap:.75rem;">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Forgot your password?</a>
            @endif
            <button class="btn btn-primary" type="submit">Log in</button>
        </div>
    </form>
</x-guest-layout>
