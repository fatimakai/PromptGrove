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
</head>
<body class="grove-guest antialiased">
    <div class="grove-auth-shell">
        <aside class="grove-auth-aside">
            <a class="grove-brand" href="{{ route('home') }}" aria-label="PromptGrove home">
                <x-application-logo aria-hidden="true" />
                <span>PromptGrove</span>
            </a>

            <div class="grove-auth-copy">
                <h1>Keep what<br><span>works.</span></h1>
                <p>Your prompts deserve somewhere better than chat history. Save them, revisit them, and keep improving.</p>
            </div>

            <svg class="grove-auth-art grove-doodle" viewBox="0 0 220 164" aria-hidden="true">
                <path d="M25 47h91c5 0 9 4 9 9v83H34c-5 0-9-4-9-9V47Z" />
                <path fill="#fffdf8" d="M50 23h104l22 22v76c0 5-4 9-9 9H59c-5 0-9-4-9-9V23Z" />
                <path d="M154 23v22h22M70 65h66M70 80h51M70 95h58" />
                <path class="accent-fill" d="M72 23h20v35l-10-8-10 8V23Z" />
                <circle cx="164" cy="124" r="25" fill="#fffdf8" />
                <path class="accent" d="m152 124 8 8 17-19" />
                <path d="M19 146c32-5 58-3 84 0 30 4 61 4 98-2" />
            </svg>
        </aside>

        <main class="grove-auth-main">
            <div class="grove-auth-card">{{ $slot }}</div>
        </main>
    </div>
</body>
</html>
