<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PromptExportController extends Controller
{
    public function show(Prompt $prompt): JsonResponse
    {
        Gate::authorize('view', $prompt);

        return response()->json($this->format($prompt->load('tags')->loadCount('versions')))
            ->header('Content-Disposition', 'attachment; filename="prompt-'.$prompt->slug.'.json"');
    }

    public function all(): StreamedResponse
    {
        $filename = 'promptgrove-export-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function (): void {
            echo json_encode(
                Prompt::query()
                    ->where('user_id', auth()->id())
                    ->with('tags')
                    ->withCount('versions')
                    ->latest()
                    ->get()
                    ->map(fn (Prompt $prompt) => $this->format($prompt)),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
        }, $filename, ['Content-Type' => 'application/json']);
    }

    private function format(Prompt $prompt): array
    {
        $data = [
            'title' => $prompt->title,
            'description' => $prompt->description,
            'prompt_text' => $prompt->prompt_text,
            'target_model' => $prompt->target_model,
            'example_input' => $prompt->example_input,
            'example_output' => $prompt->example_output,
            'visibility' => $prompt->visibility,
            'tags' => $prompt->tags->pluck('name')->values(),
            'created_at' => $prompt->created_at?->toIso8601String(),
            'updated_at' => $prompt->updated_at?->toIso8601String(),
        ];

        if (Gate::allows('viewHistory', $prompt)) {
            $data['version'] = $prompt->versions_count;
        }

        return $data;
    }
}
