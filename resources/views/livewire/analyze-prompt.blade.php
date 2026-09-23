<section @if($hasActive) wire:poll.3s @endif class="rounded-xl border border-indigo-200 bg-white p-6 shadow-sm dark:border-indigo-900 dark:bg-gray-800" aria-labelledby="ai-analysis-heading">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">PromptGrove AI</p>
            <h2 id="ai-analysis-heading" class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Analyze and improve this prompt</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Get an intent summary, concrete weaknesses, and a rewritten prompt tailored to {{ $prompt->target_model }}.</p>
        </div>
        <div class="text-right">
            <button type="button" wire:click="analyze" wire:loading.attr="disabled" @disabled($hasActive) class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="analyze">{{ $hasActive ? 'Analysis in progress' : 'Analyze prompt' }}</span>
                <span wire:loading wire:target="analyze">Starting...</span>
            </button>
            <p class="mt-2 text-xs text-gray-500">{{ $remaining }} analyses remaining this hour</p>
        </div>
    </div>

    @error('analyze')
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div>
    @enderror

    <div class="mt-6 space-y-6">
        @forelse($analyses as $analysis)
            <article wire:key="analysis-{{ $analysis->id }}" class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                    <div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Analysis from {{ $analysis->created_at->format('M j, Y \a\t g:i A') }}</span>
                        <span class="ml-2 text-xs text-gray-500">{{ $analysis->model }}</span>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $analysis->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : ($analysis->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300') }}">
                        {{ ucfirst($analysis->status) }}
                    </span>
                </header>

                @if($analysis->status === 'completed')
                    <div class="space-y-6 p-5">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500">Intent</h3>
                            <p class="mt-2 text-gray-800 dark:text-gray-100">{{ $analysis->analysis['intent_summary'] }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500">Weaknesses and ambiguities</h3>
                            <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-6 text-gray-700 dark:text-gray-200">
                                @foreach($analysis->analysis['weaknesses'] as $weakness)
                                    <li>{{ $weakness }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="grid gap-5 lg:grid-cols-2" x-data="{ copied: false }">
                            <div>
                                <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-gray-500">Before</h3>
                                <pre class="max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-950 p-4 text-sm leading-6 text-gray-100">{{ $analysis->source_prompt_text }}</pre>
                            </div>
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Improved prompt</h3>
                                    <button type="button" @click="navigator.clipboard.writeText($refs.improved.textContent.trim()); copied = true; setTimeout(() => copied = false, 1500)" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                        <span x-text="copied ? 'Copied' : 'Copy improved prompt'"></span>
                                    </button>
                                </div>
                                <pre x-ref="improved" class="max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-indigo-950 p-4 text-sm leading-6 text-indigo-50">{{ $analysis->analysis['improved_prompt'] }}</pre>
                            </div>
                        </div>

                        @if($analysis->input_tokens || $analysis->output_tokens)
                            <p class="text-xs text-gray-400">{{ number_format($analysis->input_tokens ?? 0) }} input tokens &middot; {{ number_format($analysis->output_tokens ?? 0) }} output tokens</p>
                        @endif
                    </div>
                @elseif($analysis->status === 'failed')
                    <div class="p-5">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ $analysis->failure_reason ?: 'Analysis failed. Please try again.' }}</p>
                    </div>
                @else
                    <div class="flex items-center gap-3 p-5 text-sm text-gray-600 dark:text-gray-300">
                        <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-indigo-500"></span>
                        The prompt is being analyzed. This panel will update automatically.
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 px-5 py-8 text-center text-sm text-gray-500 dark:border-gray-700">
                No analysis yet. Run one to see a structured before-and-after review.
            </div>
        @endforelse
    </div>
</section>
