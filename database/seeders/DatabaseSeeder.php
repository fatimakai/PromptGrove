<?php

namespace Database\Seeders;

use App\Models\Prompt;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\Upvote;
use App\Models\User;
use App\Services\PlatformRoleService;
use App\Services\PromptVersionService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = app(PlatformRoleService::class);
        $roles->ensure();

        $demo = User::factory()->create([
            'name' => 'PromptGrove Demo',
            'email' => 'demo@promptgrove.test',
        ]);
        $demo->assignRole(PlatformRoleService::USER);
        User::factory()->create(['name' => 'PromptGrove Admin', 'email' => 'admin@promptgrove.test'])
            ->assignRole(PlatformRoleService::ADMIN);
        User::factory()->create(['name' => 'PromptGrove Moderator', 'email' => 'moderator@promptgrove.test'])
            ->assignRole(PlatformRoleService::MODERATOR);
        $creators = User::factory(5)->create()->push($demo);
        $creators->each(function (User $user): void {
            if (! $user->hasAnyRole(PlatformRoleService::ROLES)) {
                $user->assignRole(PlatformRoleService::USER);
            }
            $user->subscriptions()->create([
                'provider' => Subscription::PROVIDER_PAYPAL,
                'provider_subscription_id' => 'I-DEMO-'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
                'provider_plan_id' => 'P-PROMPTGROVE-DEMO',
                'status' => Subscription::STATUS_ACTIVE,
                'amount' => 900,
                'currency' => 'USD',
                'current_period_start' => now(),
                'current_period_end' => now()->addYear(),
                'last_synced_at' => now(),
            ]);
        });
        $tags = collect(['marketing', 'engineering', 'research', 'support', 'writing', 'analysis'])
            ->map(fn (string $name) => Tag::create(compact('name')));
        $versions = app(PromptVersionService::class);

        Prompt::factory(30)->recycle($creators)->create()->each(function (Prompt $prompt) use ($tags, $creators, $versions): void {
            $prompt->tags()->attach($tags->random(random_int(1, 3))->pluck('id'));
            $versions->record($prompt, $prompt->user);
            if ($prompt->isPublic()) {
                $creators->random(random_int(0, min(4, $creators->count())))
                    ->each(fn (User $user) => Upvote::firstOrCreate(['user_id' => $user->id, 'prompt_id' => $prompt->id]));
            }
        });
    }
}
