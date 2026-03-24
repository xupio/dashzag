@php
    $manifestPath = public_path('build/manifest.json');
    $manifest = [];

    if (is_file($manifestPath)) {
        $decodedManifest = json_decode(file_get_contents($manifestPath), true);
        $manifest = is_array($decodedManifest) ? $decodedManifest : [];
    }

    $appCss = $manifest['resources/css/app.css']['file'] ?? null;
    $appJs = $manifest['resources/js/app.js']['file'] ?? null;
@endphp

@if ($appCss)
    <link rel="stylesheet" href="{{ asset('build/' . $appCss) }}">
@endif

@if ($appJs)
    <script type="module" src="{{ asset('build/' . $appJs) }}"></script>
@endif
