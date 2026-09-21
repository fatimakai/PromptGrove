<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PromptForge — Build better prompts together</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 font-sans text-white">
    <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6">
        <a href="{{ route('home') }}" class="text-xl font-bold">Prompt<span class="text-indigo-400">Forge</span></a>
        <nav class="flex items-center gap-4">
            <a href="{{ route('prompts.index') }}" class="text-sm text-gray-300 hover:text-white">Discover prompts</a>
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-950">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-gray-300 hover:text-white">Log in</a>
                <a href="{{ config('demo.oauth_only') ? route('login') : route('register') }}" class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-semibold hover:bg-indigo-400">Get started</a>
            @endauth
        </nav>
    </header>

    <main>
        <section class="mx-auto max-w-5xl px-6 py-28 text-center">
            <p class="mb-5 text-sm font-semibold uppercase tracking-[0.3em] text-indigo-400">A shared prompt workspace</p>
            <h1 class="text-5xl font-bold tracking-tight sm:text-7xl">Forge prompts your team can trust.</h1>
            <p class="mx-auto mt-7 max-w-2xl text-lg leading-8 text-gray-300">Store, discover, test, and refine the prompts behind your AI products—all in one searchable library.</p>
            <div class="mt-10 flex justify-center gap-4">
                <a href="{{ route('prompts.index') }}" class="rounded-lg bg-indigo-500 px-6 py-3 font-semibold hover:bg-indigo-400">Explore the library</a>
                @guest<a href="{{ config('demo.oauth_only') ? route('login') : route('register') }}" class="rounded-lg border border-gray-700 px-6 py-3 font-semibold hover:border-gray-500">{{ config('demo.oauth_only') ? 'Continue with Google or GitHub' : 'Create your workspace' }}</a>@endguest
            </div>
        </section>

        <section class="mx-auto grid max-w-6xl gap-6 px-6 pb-24 md:grid-cols-3">
            @foreach([
                ['Searchable by design', 'Find prompts by purpose, target model, or tag instead of digging through old documents.'],
                ['Built for iteration', 'Compare every meaningful edit, restore older versions safely, and keep examples alongside each prompt.'],
                ['Share with confidence', 'Publish useful prompts to the community or keep private work in your own library.'],
            ] as [$title, $copy])
                <article class="rounded-2xl border border-gray-800 bg-gray-900 p-7">
                    <h2 class="text-lg font-semibold">{{ $title }}</h2>
                    <p class="mt-3 text-sm leading-6 text-gray-400">{{ $copy }}</p>
                </article>
            @endforeach
        </section>
    </main>
</body>
</html>
