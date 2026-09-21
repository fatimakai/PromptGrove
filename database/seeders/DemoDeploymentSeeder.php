<?php

namespace Database\Seeders;

use App\Models\Prompt;
use App\Models\User;
use App\Services\PlatformRoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoDeploymentSeeder extends Seeder
{
    public function run(): void
    {
        $roles = app(PlatformRoleService::class);
        $roles->ensure();

        $this->provisionStaff($roles);

        // Never run DatabaseSeeder in public: it creates known-password accounts.
        $author = User::query()->firstOrCreate(
            ['email' => 'library@promptforge.invalid'],
            [
                'name' => 'PromptForge Library',
                'password' => Hash::make(Str::random(64)),
            ],
        );
        if (! $author->hasVerifiedEmail()) {
            $author->markEmailAsVerified();
        }
        $roles->assignDefault($author);

        foreach ($this->publicPrompts() as $prompt) {
            Prompt::query()->firstOrCreate(['slug' => $prompt['slug']], [
                'user_id' => $author->id,
                'title' => $prompt['title'],
                'description' => $prompt['description'],
                'prompt_text' => $prompt['prompt_text'],
                'target_model' => $prompt['target_model'],
                'visibility' => Prompt::VISIBILITY_PUBLIC,
            ]);
        }
    }

    private function provisionStaff(PlatformRoleService $roles): void
    {
        $staff = config('demo.staff');
        $emails = array_filter(array_map(fn (array $account) => $account['email'], $staff));

        if (count($emails) !== count(array_unique($emails))) {
            throw new RuntimeException('Demo staff email addresses must be distinct.');
        }

        foreach (['admin' => PlatformRoleService::ADMIN, 'moderator' => PlatformRoleService::MODERATOR] as $key => $role) {
            $email = $staff[$key]['email'];
            $password = $staff[$key]['password'];

            if (! $email && ! $password) {
                continue;
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($password) || strlen($password) < 16) {
                throw new RuntimeException("Demo {$key} needs a valid email and a private password of at least 16 characters.");
            }

            $user = User::query()->where('email', $email)->first();
            if ($user && ! $user->hasRole($role)) {
                throw new RuntimeException("Demo {$key} email already belongs to another account; refusing to promote it.");
            }

            $user ??= User::query()->create([
                'name' => 'PromptForge '.ucfirst($key),
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
            if (! Hash::check($password, $user->password)) {
                $user->update(['password' => Hash::make($password)]);
            }
        }
    }

    private function publicPrompts(): array
    {
        return [
            [
                'slug' => 'summarize-customer-interviews',
                'title' => 'Summarize customer interviews',
                'description' => 'Turn raw interview notes into product insights.',
                'target_model' => 'Model agnostic',
                'prompt_text' => 'Summarize these customer interview notes. Group recurring needs, distinguish direct quotes from interpretation, and cite the note supporting each insight. Notes: {{notes}}',
            ],
            [
                'slug' => 'review-api-change',
                'title' => 'Review an API change',
                'description' => 'Identify compatibility and security risks before release.',
                'target_model' => 'Model agnostic',
                'prompt_text' => 'Review the proposed API change for backwards compatibility, validation gaps, authorization risks, and test coverage. Return findings by severity with concrete evidence. Change: {{change}}',
            ],
            [
                'slug' => 'draft-support-reply',
                'title' => 'Draft a support reply',
                'description' => 'Produce a clear response without inventing a resolution.',
                'target_model' => 'Model agnostic',
                'prompt_text' => 'Draft a concise, empathetic support reply based on the ticket and known facts. Do not promise a fix that is not confirmed. Ticket: {{ticket}} Known facts: {{facts}}',
            ],
            [
                'slug' => 'extract-structured-action-items',
                'title' => 'Extract structured action items',
                'description' => 'Convert meeting notes into JSON tasks.',
                'target_model' => 'Model agnostic',
                'prompt_text' => 'Extract action items from the meeting notes. Return only a JSON array of objects with task, owner, due_date, and source_quote. Use null for unknown fields. Notes: {{notes}}',
            ],
        ];
    }
}
