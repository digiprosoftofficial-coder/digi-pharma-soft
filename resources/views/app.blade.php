<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $branding = \App\Support\Platform\MarketingSiteSettings::get();
    $iconUrl = $branding['icon_url'] ?? '/brand/softopharma-icon.png';
    $iconVersion = @filemtime(public_path('brand/softopharma-icon.png')) ?: time();
    $faviconVersion = @filemtime(public_path('favicon.ico')) ?: $iconVersion;
    $bust = static fn (string $path, int $version): string => $path.(str_contains($path, '?') ? '&' : '?').'v='.$version;
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#123D32">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $branding['site_name'] }}">
    <meta name="application-name" content="{{ $branding['site_name'] }}">
    <meta name="keywords" content="{{ $branding['seo_keywords'] }}">
    <link rel="icon" href="{{ $bust('/favicon.ico', $faviconVersion) }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $bust('/favicon-16x16.png', $faviconVersion) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $bust('/favicon-32x32.png', $faviconVersion) }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ $bust('/favicon.png', $faviconVersion) }}">
    <link rel="icon" type="image/png" href="{{ $bust($iconUrl, $iconVersion) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $bust('/icons/apple-touch-icon.png', $iconVersion) }}">
    <link rel="shortcut icon" href="{{ $bust('/favicon.ico', $faviconVersion) }}">
    <link rel="manifest" href="/build/manifest.webmanifest">
    <title inertia>{{ $branding['seo_title'] ?? config('app.name', 'SoftoPharma') }}</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-light">
@inertia
</body>
</html>
