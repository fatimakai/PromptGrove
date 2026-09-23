<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_load(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('PromptGrove')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
        $this->get('/prompts')->assertOk()->assertSee('Discover prompts');
    }

    public function test_authenticated_workspace_pages_load(): void
    {
        $user = User::factory()->create();
        Prompt::factory()->for($user)->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/prompts/mine')->assertOk();
        $this->actingAs($user)->get('/prompts/bookmarked')->assertOk();
        $this->actingAs($user)->get('/prompts/create')->assertOk();
    }
}
