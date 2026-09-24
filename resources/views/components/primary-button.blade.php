<button {{ $attributes->merge(['type' => 'submit', 'class' => 'grove-button grove-button-primary uppercase tracking-wider disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
