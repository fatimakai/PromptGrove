<nav x-data="{ open: false }" class="grove-app-nav">
    <div class="grove-shell grove-nav-inner">
        <div class="flex min-w-0 items-center gap-8">
            <a class="grove-brand" href="{{ route('home') }}" aria-label="PromptGrove home">
                <x-application-logo aria-hidden="true" />
                <span>PromptGrove</span>
            </a>

            <div class="grove-nav-links" aria-label="Primary navigation">
                <a href="{{ route('prompts.index') }}" class="grove-nav-link {{ request()->routeIs('prompts.index') ? 'is-active' : '' }}">Discover</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="grove-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Dashboard</a>
                    <a href="{{ route('prompts.mine') }}" class="grove-nav-link {{ request()->routeIs('prompts.mine') ? 'is-active' : '' }}">My prompts</a>
                    <a href="{{ route('prompts.bookmarked') }}" class="grove-nav-link {{ request()->routeIs('prompts.bookmarked') ? 'is-active' : '' }}">Bookmarks</a>
                    <a href="{{ route('collections.index') }}" class="grove-nav-link {{ request()->routeIs('collections.*') ? 'is-active' : '' }}">Collections</a>
                    <a href="{{ route('billing.index') }}" class="grove-nav-link {{ request()->routeIs('billing.*') ? 'is-active' : '' }}">{{ auth()->user()->isPro() ? 'Pro' : 'Upgrade' }}</a>
                    @can('moderate prompts')<a href="{{ route('moderation.index') }}" class="grove-nav-link {{ request()->routeIs('moderation.*') ? 'is-active' : '' }}">Moderate</a>@endcan
                    @can('manage users')<a href="{{ route('admin.users.index') }}" class="grove-nav-link {{ request()->routeIs('admin.*') ? 'is-active' : '' }}">Admin</a>@endcan
                @endauth
            </div>
        </div>

        <div class="grove-nav-account flex items-center gap-4">
            @auth
                <a href="{{ route('prompts.create') }}" class="grove-nav-action">New prompt <span aria-hidden="true">&plus;</span></a>
                <a href="{{ route('profile.edit') }}" class="grove-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">{{ auth()->user()->name }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-xs font-semibold text-gray-500 hover:text-red-700">Log out</button></form>
            @else
                <a href="{{ route('login') }}" class="grove-nav-link">Log in</a>
                <a href="{{ config('demo.oauth_only') ? route('login') : route('register') }}" class="grove-nav-action">Get started <span aria-hidden="true">&rarr;</span></a>
            @endauth
        </div>

        <button type="button" @click="open = !open" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-900 lg:hidden" aria-label="Toggle navigation" :aria-expanded="open">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>

    <div x-show="open" x-cloak class="grove-shell space-y-3 border-t border-gray-300 py-5 lg:hidden">
        <a href="{{ route('prompts.index') }}" class="block text-sm font-semibold">Discover</a>
        @auth
            <a href="{{ route('dashboard') }}" class="block text-sm font-semibold">Dashboard</a>
            <a href="{{ route('prompts.mine') }}" class="block text-sm font-semibold">My prompts</a>
            <a href="{{ route('prompts.bookmarked') }}" class="block text-sm font-semibold">Bookmarks</a>
            <a href="{{ route('collections.index') }}" class="block text-sm font-semibold">Collections</a>
            <a href="{{ route('billing.index') }}" class="block text-sm font-semibold">{{ auth()->user()->isPro() ? 'Pro plan' : 'Upgrade to Pro' }}</a>
            @can('moderate prompts')<a href="{{ route('moderation.index') }}" class="block text-sm font-semibold">Moderate</a>@endcan
            @can('manage users')<a href="{{ route('admin.users.index') }}" class="block text-sm font-semibold">Admin</a>@endcan
            <a href="{{ route('prompts.create') }}" class="block text-sm font-semibold text-amber-800">New prompt</a>
            <a href="{{ route('profile.edit') }}" class="block text-sm font-semibold">Profile</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-semibold text-red-700">Log out</button></form>
        @else
            <a href="{{ route('login') }}" class="block text-sm font-semibold">Log in</a>
            <a href="{{ config('demo.oauth_only') ? route('login') : route('register') }}" class="block text-sm font-semibold text-amber-800">{{ config('demo.oauth_only') ? 'Continue with OAuth' : 'Create account' }}</a>
        @endauth
    </div>
</nav>
