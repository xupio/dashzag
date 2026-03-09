<x-guest-layout>
    <h1 class="page-title" style="font-size:1.2rem; margin-bottom:1rem;">Create Account</h1>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label class="label" for="name">Name</label>
            <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            @error('email')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="label" for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="label" for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
            @error('password_confirmation')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; gap:.75rem;">
            <a href="{{ route('login') }}">Already registered?</a>
            <button class="btn btn-primary" type="submit">Register</button>
        </div>
    </form>
</x-guest-layout>
