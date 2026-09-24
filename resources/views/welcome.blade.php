<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f7f3">
    <meta name="description" content="A prompt library for ideas worth keeping, refining, and sharing.">
    <title>PromptGrove &mdash; Prompts worth keeping</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=barlow-condensed:600,700,800|ibm-plex-mono:400,500|ibm-plex-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grove-landing">
    <svg class="grove-ripple-definitions" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><defs><path id="grove-feature-ripple" d="
        M8 4 C10 1.5 12 1.5 14 4 C16 6.5 18 6.5 20 4 C22 1.5 24 1.5 26 4 C28 6.5 30 6.5 32 4 C34 1.5 36 1.5 38 4 C40 6.5 42 6.5 44 4 C46 1.5 48 1.5 50 4 C52 6.5 54 6.5 56 4 C58 1.5 60 1.5 62 4 C64 6.5 66 6.5 68 4 C70 1.5 72 1.5 74 4 C76 6.5 78 6.5 80 4 C82 1.5 84 1.5 86 4 C88 6.5 90 6.5 92 4 Q96 4 96 8
        C98.5 10 98.5 12 96 14 C93.5 16 93.5 18 96 20 C98.5 22 98.5 24 96 26 C93.5 28 93.5 30 96 32 C98.5 34 98.5 36 96 38 C93.5 40 93.5 42 96 44 C98.5 46 98.5 48 96 50 C93.5 52 93.5 54 96 56 C98.5 58 98.5 60 96 62 C93.5 64 93.5 66 96 68 C98.5 70 98.5 72 96 74 C93.5 76 93.5 78 96 80 C98.5 82 98.5 84 96 86 C93.5 88 93.5 90 96 92 Q96 96 92 96
        C90 98.5 88 98.5 86 96 C84 93.5 82 93.5 80 96 C78 98.5 76 98.5 74 96 C72 93.5 70 93.5 68 96 C66 98.5 64 98.5 62 96 C60 93.5 58 93.5 56 96 C54 98.5 52 98.5 50 96 C48 93.5 46 93.5 44 96 C42 98.5 40 98.5 38 96 C36 93.5 34 93.5 32 96 C30 98.5 28 98.5 26 96 C24 93.5 22 93.5 20 96 C18 98.5 16 98.5 14 96 C12 93.5 10 93.5 8 96 Q4 96 4 92
        C1.5 90 1.5 88 4 86 C6.5 84 6.5 82 4 80 C1.5 78 1.5 76 4 74 C6.5 72 6.5 70 4 68 C1.5 66 1.5 64 4 62 C6.5 60 6.5 58 4 56 C1.5 54 1.5 52 4 50 C6.5 48 6.5 46 4 44 C1.5 42 1.5 40 4 38 C6.5 36 6.5 34 4 32 C1.5 30 1.5 28 4 26 C6.5 24 6.5 22 4 20 C1.5 18 1.5 16 4 14 C6.5 12 6.5 10 4 8 Q4 4 8 4 Z
    "/></defs></svg>

    <header class="landing-header"><div class="grove-shell landing-header-inner">
        <a class="grove-brand" href="{{ route('home') }}" aria-label="PromptGrove home"><x-application-logo aria-hidden="true" /><span>PromptGrove</span></a>
        <nav class="landing-nav" aria-label="Main navigation">
            <a href="{{ route('prompts.index') }}">Explore prompts</a>
            <a href="#features">What it does</a>
            <a href="#why">Why keep them?</a>
        </nav>
        <div class="landing-actions">
            @auth
                <a class="landing-login" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="landing-cta" href="{{ route('prompts.mine') }}">Open workspace &rarr;</a>
            @else
                <a class="landing-login" href="{{ route('login') }}">Log in</a>
                <a class="landing-cta" href="{{ config('demo.oauth_only') ? route('login') : route('register') }}">Get started &rarr;</a>
            @endauth
        </div>
    </div></header>

    <main>
        <section class="landing-hero" aria-labelledby="hero-title"><div class="grove-shell landing-hero-inner">
            <div class="landing-hero-title">
                <h1 id="hero-title" class="grove-display">Prompts<br>worth <span>keeping.</span></h1>
                <svg class="landing-hero-mark grove-doodle" viewBox="0 0 90 65" aria-hidden="true"><path d="M61 8 31 38c-7 7-7 17 0 22 6 5 15 4 21-2l27-27c5-5 5-12 0-17s-12-5-17 0L37 39c-3 3-3 7 0 10s7 3 10 0l23-23" /></svg>
            </div>
            <div class="landing-hero-side">
                <svg class="landing-hero-sketch grove-doodle" viewBox="0 0 150 144" aria-hidden="true">
                    <path d="M33 15h47l21 21v76c0 3-3 5-6 5H33c-3 0-5-2-5-5V20c0-3 2-5 5-5Z" />
                    <path d="M80 15v21h21M41 58h47M41 71h42M41 84h29" />
                    <path class="accent-fill" d="M43 15h18v29l-9-7-9 7Z" />
                    <circle cx="98" cy="105" r="18" fill="#f7f7f3" />
                    <path class="accent" d="m89 105 6 6 12-14" />
                </svg>
                <p class="landing-hero-description">Stop letting a good prompt get lost in chat history. Save what works, improve what doesn't, and find it again when you need it.</p>
                <div class="landing-hero-actions">
                    <a class="landing-primary" href="{{ route('prompts.index') }}">Explore the library <span aria-hidden="true">&rarr;</span></a>
                    <a class="landing-text-link" href="#features">See how it works &rarr;</a>
                </div>
            </div>
        </div></section>

        <div class="landing-wave" aria-hidden="true"><svg viewBox="0 0 1440 30" preserveAspectRatio="none"><path d="M0 16c120-8 160 7 281 0s191-8 299-1 177 7 280-1 192-9 286-1 187 8 294-1"/></svg></div>

        <section id="features" class="landing-features"><div class="grove-shell">
            <div class="landing-section-heading"><h2 class="grove-display">From first draft<br>to favorite tool.</h2></div>
            <div class="landing-feature-grid">
                <article class="landing-feature-card">
                    <svg class="grove-ripple-frame" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><use href="#grove-feature-ripple"/></svg>
                    <div class="landing-feature-head"><span class="landing-feature-number grove-mono">01 / FIND</span>
                        <svg class="landing-feature-art grove-doodle" viewBox="0 0 108 102" aria-hidden="true"><path d="M16 15h53v64H16zM26 29h32M26 41h27M26 53h21"/><circle cx="69" cy="65" r="18" fill="#ffffff"/><path d="m82 79 16 16"/><path class="accent" d="M59 64c3-4 7-6 12-6"/></svg>
                    </div>
                    <h3 class="grove-display">Find the right prompt.</h3>
                    <p>Search by purpose, model, and tag. Keep personal work private or discover what others have shared.</p>
                </article>
                <article class="landing-feature-card">
                    <svg class="grove-ripple-frame" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><use href="#grove-feature-ripple"/></svg>
                    <div class="landing-feature-head"><span class="landing-feature-number grove-mono">02 / IMPROVE</span>
                        <svg class="landing-feature-art grove-doodle" viewBox="0 0 108 102" aria-hidden="true"><path d="M17 15h56v70H17zM28 30h31M28 42h25M28 54h18"/><path d="m55 82 6-17 27-29 9 9-28 28-14 9ZM82 42l9 9"/><path class="accent-fill" d="m55 82 6-17 8 8-14 9Z"/></svg>
                    </div>
                    <h3 class="grove-display">Improve it if you want to.</h3>
                    <p>Get structured AI feedback, compare meaningful edits, and restore an earlier version when needed.</p>
                </article>
                <article class="landing-feature-card">
                    <svg class="grove-ripple-frame" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><use href="#grove-feature-ripple"/></svg>
                    <div class="landing-feature-head"><span class="landing-feature-number grove-mono">03 / SHARE</span>
                        <svg class="landing-feature-art grove-doodle" viewBox="0 0 108 102" aria-hidden="true"><path d="M39 46c13-7 22-13 31-18M39 57c12 7 22 13 31 18"/><circle class="accent-fill" cx="28" cy="52" r="11"/><circle cx="80" cy="22" r="11" fill="#ffffff"/><circle cx="80" cy="81" r="11" fill="#ffffff"/></svg>
                    </div>
                    <h3 class="grove-display">Save it and share it with others.</h3>
                    <p>Move work into shared collections with clear roles, invite links, and an audit trail.</p>
                </article>
            </div>
        </div></section>

        <section id="why" class="landing-closing">
            <svg class="landing-closing-doodle" viewBox="0 0 150 150" aria-hidden="true"><path d="M18 73 131 18 89 126 65 93 18 73ZM65 93 131 18M65 93l-3 27 27 6" /></svg>
            <div class="grove-shell landing-closing-inner">
                <div><h2 class="grove-display">Less starting over.<br>More building on.</h2><p>PromptGrove treats a prompt like a working document: something you can test, revisit, improve, and confidently pass along.</p></div>
                <a href="{{ route('prompts.index') }}">Explore prompts <span aria-hidden="true">&rarr;</span></a>
            </div>
        </section>
    </main>

    <footer class="landing-footer"><div class="grove-shell landing-footer-inner"><strong>PromptGrove<span style="color:#815309">.</span></strong><span>A library for ideas worth keeping.</span><span>Built to iterate.</span></div></footer>
</body>
</html>
