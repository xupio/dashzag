<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('branding/zag-smal.png') }}">
    @include('layout.partials.vite-app-assets')
    <link rel="stylesheet" href="{{ asset('nobleui/css/nobleui.css') }}">
    @stack('styles')
</head>
<body class="nobleui-body">
<div class="main-wrapper">
    @include('layouts.navigation')

    <div class="page-wrapper">
        <div class="navbar">
            <div style="display:flex; align-items:center; gap:.65rem;">
                <button type="button" class="btn-icon sidebar-toggle" data-sidebar-toggle aria-label="Toggle sidebar">?</button>
                <strong>{{ request()->routeIs('dashboard') ? 'Dashboard' : 'Account' }}</strong>
            </div>

            <div class="dropdown">
                <button type="button" class="btn btn-outline" data-dropdown-toggle>
                    {{ Auth::user()->name }}
                </button>
                <div class="dropdown-menu">
                    <a href="{{ route('profile.edit') }}">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Log Out</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="content-area">
            @isset($header)
                <h1 class="page-title">{{ $header }}</h1>
            @endisset

            {{ $slot }}
        </div>

        <footer class="footer">
            {{ now()->format('Y') }} {{ config('app.name', 'Laravel') }}. Dashboard powered by NobleUI style layout.
        </footer>
    </div>
</div>

<script src="{{ asset('nobleui/js/nobleui.js') }}"></script>
@stack('scripts')
</body>
</html>

