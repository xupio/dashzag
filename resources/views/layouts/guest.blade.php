<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('nobleui/css/nobleui.css') }}">
    @stack('styles')
</head>
<body class="nobleui-body">
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">{{ config('app.name', 'Laravel') }}</div>
        {{ $slot }}
        <div class="auth-footer">Secure access portal</div>
    </div>
</div>

<script src="{{ asset('nobleui/js/nobleui.js') }}"></script>
@stack('scripts')
</body>
</html>
