@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-green-700 bg-green-50 px-4 py-3 font-medium text-sm text-green-800']) }}>
        {{ $status }}
    </div>
@endif
