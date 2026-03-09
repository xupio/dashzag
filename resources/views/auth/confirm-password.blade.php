<x-guest-layout>
    <h1 class="page-title" style="font-size:1.2rem; margin-bottom:1rem;">Confirm Password</h1>
    <p class="muted" style="margin-bottom:1rem;">Please confirm your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="form-group">
            <label class="label" for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div style="text-align:right;">
            <button class="btn btn-primary" type="submit">Confirm</button>
        </div>
    </form>
</x-guest-layout>
