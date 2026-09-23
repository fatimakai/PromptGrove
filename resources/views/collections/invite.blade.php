<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Collection invitation</h1></x-slot>
    <div class="mx-auto max-w-xl px-4 py-16 sm:px-6">
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">PromptGrove shared collection</p>
            <h2 class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ $collection->name }}</h2>
            <p class="mt-2 text-gray-600 dark:text-gray-300">You will join as a <strong>{{ $collection->invite_role }}</strong>. Collection prompts remain private to members.</p>
            <form method="POST" action="{{ route('collections.join', $token) }}" class="mt-6">@csrf<button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Join collection</button></form>
            <p class="mt-4 text-xs text-gray-500">This invitation expires {{ $collection->invite_expires_at->diffForHumans() }}.</p>
        </div>
    </div>
</x-app-layout>
