@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 bg-white text-gray-900 focus:border-gray-900 focus:ring-amber-300 rounded-xl shadow-none']) }}>
