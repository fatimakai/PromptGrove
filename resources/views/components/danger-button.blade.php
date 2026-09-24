<button {{ $attributes->merge(['type' => 'submit', 'class' => 'grove-button grove-button-danger uppercase tracking-wider']) }}>
    {{ $slot }}
</button>
