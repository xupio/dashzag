<x-guest-layout>
    <h1 class="page-title" style="font-size:1.2rem; margin-bottom:1rem;">Verify Email</h1>
    <div class="alert alert-info">Thanks for signing up. Please verify your email using the link we sent.</div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">A new verification link has been sent to your email address.</div>
    @endif

    <div style="display:flex; justify-content:space-between; gap:.75rem; flex-wrap:wrap;">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Resend Verification Email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline">Log Out</button>
        </form>
    </div>
</x-guest-layout>
