<button {{ $attributes->merge(['type' => 'button', 'class' => 'grove-button grove-button-secondary uppercase tracking-wider disabled:cursor-not-allowed disabled:opacity-40']) }}>
    {{ $slot }}
</button>
