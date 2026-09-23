<x-app-layout>
    <x-slot name="header">
        <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Plan and billing</h1><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sandbox checkout only—no real transactions are accepted.</p></div>
    </x-slot>
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
        @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">{{ session('error') }}</div>@endif
        @if(request('cancelled'))<div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">Checkout was cancelled. Your plan was not changed.</div>@endif

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-7 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-semibold text-gray-500">Free</p><p class="mt-2 text-3xl font-black dark:text-white">$0</p><ul class="mt-5 space-y-3 text-sm text-gray-600 dark:text-gray-300"><li>Personal prompts</li><li>5 AI analyses per hour</li><li>Public discovery, bookmarks, and JSON export</li></ul>
            </section>
            <section class="rounded-xl border-2 border-indigo-500 bg-white p-7 shadow-sm dark:bg-gray-800">
                <div class="flex items-center justify-between"><p class="text-sm font-semibold text-indigo-600">PromptGrove Pro</p>@if($activeSubscription)<span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700">Active</span>@endif</div><p class="mt-2 text-3xl font-black dark:text-white">$9<span class="text-base font-medium text-gray-500">/month USD</span></p><ul class="mt-5 space-y-3 text-sm text-gray-600 dark:text-gray-300"><li>Everything in Free</li><li>Prompt version history and restore</li><li>Private shared collections with team roles</li><li>25 AI analyses per hour</li></ul>
                @if(! $activeSubscription)
                    <div class="mt-6 grid gap-3"><form method="POST" action="{{ route('billing.razorpay') }}">@csrf<button @disabled(! $razorpayConfigured) class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Upgrade with Razorpay test mode</button></form><form method="POST" action="{{ route('billing.paypal') }}">@csrf<button @disabled(! $paypalConfigured) class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Upgrade with PayPal sandbox</button></form></div>
                    @if(! $razorpayConfigured || ! $paypalConfigured)<p class="mt-3 text-xs text-gray-500">Unavailable buttons need their sandbox credentials and plan ID in <code>.env</code>.</p>@endif
                @else
                    <div class="mt-6 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"><p class="font-semibold">Active via {{ ucfirst($activeSubscription->provider) }}</p><p class="mt-1">@if($activeSubscription->current_period_end)Renews or expires {{ $activeSubscription->current_period_end->format('M j, Y') }}.@elseThe provider will supply the next billing date by webhook.@endif</p></div><form method="POST" action="{{ route('billing.cancel', $activeSubscription) }}" class="mt-4" onsubmit="return confirm('Cancel Pro immediately in the sandbox?')">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600">Cancel subscription</button></form>
                @endif
            </section>
        </div>

        @if($subscriptions->isNotEmpty())<section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"><h2 class="font-semibold dark:text-white">Subscription activity</h2><div class="mt-4 divide-y divide-gray-200 text-sm dark:divide-gray-700">@foreach($subscriptions as $subscription)<div class="flex items-center justify-between gap-4 py-3"><div><span class="font-semibold capitalize dark:text-white">{{ $subscription->provider }}</span><span class="ml-2 text-xs text-gray-500">{{ $subscription->provider_subscription_id }}</span></div><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold capitalize text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $subscription->status }}</span></div>@endforeach</div></section>@endif
    </div>
</x-app-layout>
