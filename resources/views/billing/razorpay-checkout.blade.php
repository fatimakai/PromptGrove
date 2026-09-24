<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Complete Razorpay test checkout</h1></x-slot>
    <div class="mx-auto max-w-xl px-4 py-16 sm:px-6">
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800"><p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Test mode</p><h2 class="mt-3 text-2xl font-bold dark:text-white">PromptGrove Pro — $9/month</h2><p class="mt-2 text-sm text-gray-500">Subscription {{ $subscription->provider_subscription_id }}</p><button id="razorpay-checkout" class="mt-6 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Open secure test checkout</button><a href="{{ route('billing.index') }}" class="mt-4 block text-sm font-semibold text-gray-500">Cancel</a></div>
        <form id="razorpay-confirm" method="POST" action="{{ route('billing.razorpay.confirm') }}" class="hidden">@csrf<input name="razorpay_payment_id"><input name="razorpay_subscription_id"><input name="razorpay_signature"></form>
    </div>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.getElementById('razorpay-checkout').addEventListener('click', function () {
            const checkout = new Razorpay({
                key: @js(config('billing.razorpay.key_id')),
                subscription_id: @js($subscription->provider_subscription_id),
                name: 'PromptGrove',
                description: 'Pro monthly subscription (sandbox)',
                prefill: { name: @js(auth()->user()->name), email: @js(auth()->user()->email) },
                theme: { color: '#e6a73b' },
                handler: function (response) {
                    const form = document.getElementById('razorpay-confirm');
                    form.elements.razorpay_payment_id.value = response.razorpay_payment_id;
                    form.elements.razorpay_subscription_id.value = response.razorpay_subscription_id;
                    form.elements.razorpay_signature.value = response.razorpay_signature;
                    form.submit();
                }
            });
            checkout.open();
        });
    </script>
</x-app-layout>
