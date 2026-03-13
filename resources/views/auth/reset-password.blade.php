<x-guest-layout>
    <h1 class="page-title" style="font-size:1.2rem; margin-bottom:1rem;">Reset Password</h1>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-group">
            <label class="label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
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

        <div style="text-align:right;">
            <button class="btn btn-primary" type="submit">Reset Password</button>
        </div>
    </form>
</x-guest-layout>

