<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f7f7f3">
    <title>{{ config('app.name', 'PromptGrove') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=barlow-condensed:600,700,800|ibm-plex-mono:400,500|ibm-plex-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="grove-theme antialiased">
    <div class="min-h-screen">
        @include('layouts.navigation')

        @isset($header)
            <header class="grove-page-header">
                <div class="grove-shell grove-page-header-inner">{{ $header }}</div>
            </header>
        @endisset

        <main class="grove-app-main">{{ $slot }}</main>
    </div>
    @livewireScripts
</body>
</html>
