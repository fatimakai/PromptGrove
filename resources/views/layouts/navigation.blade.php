<nav x-data="{ open: false }" class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Prompt<span class="text-indigo-600">Forge</span></a>
                <div class="hidden items-center gap-6 sm:flex">
                    <a href="{{ route('prompts.index') }}" class="text-sm font-medium {{ request()->routeIs('prompts.index') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}">Discover</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}">Dashboard</a>
                        <a href="{{ route('prompts.mine') }}" class="text-sm font-medium {{ request()->routeIs('prompts.mine') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}">My prompts</a>
                        <a href="{{ route('prompts.bookmarked') }}" class="text-sm font-medium {{ request()->routeIs('prompts.bookmarked') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}">Bookmarks</a>
                        <a href="{{ route('collections.index') }}" class="text-sm font-medium {{ request()->routeIs('collections.*') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}">Collections</a>
                        <a href="{{ route('billing.index') }}" class="text-sm font-medium {{ request()->routeIs('billing.*') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}">{{ auth()->user()->isPro() ? 'Pro' : 'Upgrade' }}</a>
                        @can('moderate prompts')<a href="{{ route('moderation.index') }}" class="text-sm font-medium {{ request()->routeIs('moderation.*') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}">Moderate</a>@endcan
                        @can('manage users')<a href="{{ route('admin.users.index') }}" class="text-sm font-medium {{ request()->routeIs('admin.*') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}">Admin</a>@endcan
                    @endauth
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                @auth
                    <a href="{{ route('prompts.create') }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">New prompt</a>
                    <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-medium text-gray-500 hover:text-red-600">Log out</button></form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 dark:text-gray-200">Log in</a>
                    <a href="{{ config('demo.oauth_only') ? route('login') : route('register') }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Get started</a>
                @endauth
            </div>

            <button type="button" @click="open = !open" class="rounded-md p-2 sm:hidden" aria-label="Toggle navigation">☰</button>
        </div>

        <div x-show="open" x-cloak class="space-y-3 border-t border-gray-200 py-4 sm:hidden dark:border-gray-700">
            <a href="{{ route('prompts.index') }}" class="block text-sm font-medium">Discover</a>
            @auth
                <a href="{{ route('dashboard') }}" class="block text-sm font-medium">Dashboard</a>
                <a href="{{ route('prompts.mine') }}" class="block text-sm font-medium">My prompts</a>
                <a href="{{ route('prompts.bookmarked') }}" class="block text-sm font-medium">Bookmarks</a>
                <a href="{{ route('collections.index') }}" class="block text-sm font-medium">Collections</a>
                <a href="{{ route('billing.index') }}" class="block text-sm font-medium">{{ auth()->user()->isPro() ? 'Pro plan' : 'Upgrade to Pro' }}</a>
                @can('moderate prompts')<a href="{{ route('moderation.index') }}" class="block text-sm font-medium">Moderate</a>@endcan
                @can('manage users')<a href="{{ route('admin.users.index') }}" class="block text-sm font-medium">Admin</a>@endcan
                <a href="{{ route('prompts.create') }}" class="block text-sm font-medium text-indigo-600">New prompt</a>
                <a href="{{ route('profile.edit') }}" class="block text-sm font-medium">Profile</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-medium text-red-600">Log out</button></form>
            @else
                <a href="{{ route('login') }}" class="block text-sm font-medium">Log in</a>
                <a href="{{ config('demo.oauth_only') ? route('login') : route('register') }}" class="block text-sm font-medium text-indigo-600">{{ config('demo.oauth_only') ? 'Continue with OAuth' : 'Create account' }}</a>
            @endauth
        </div>
    </div>
</nav>
