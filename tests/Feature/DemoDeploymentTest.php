<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Models\User;
use App\Services\PlatformRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_demo_blocks_email_registration_and_reset_at_the_endpoint(): void
    {
        config(['demo.oauth_only' => true]);

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Imposter', 'email' => 'victim@example.com', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'victim@example.com'])->assertNotFound();
        $this->get('/reset-password/token')->assertNotFound();
        $this->post('/reset-password', ['email' => 'victim@example.com'])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'victim@example.com']);
        $this->get('/login')->assertOk()->assertSee('Continue with Google or GitHub');
    }

    public function test_public_demo_rejects_email_changes_but_still_allows_name_updates(): void
    {
        config(['demo.oauth_only' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Renamed', 'email' => 'victim@example.com',
        ])->assertSessionHasErrors('email');
        $this->assertSame($user->email, $user->fresh()->email);

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Renamed', 'email' => $user->email,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $user->fresh()->name);
    }

    public function test_production_demo_seeder_is_idempotent_and_creates_only_public_prompts(): void
    {
        config(['demo.staff.admin' => ['email' => 'owner@example.com', 'password' => 'a-strong-private-admin-secret']]);
        config(['demo.staff.moderator' => ['email' => 'moderator@example.com', 'password' => 'a-strong-private-moderator-secret']]);

        $this->seed(\Database\Seeders\DemoDeploymentSeeder::class);
        $this->seed(\Database\Seeders\DemoDeploymentSeeder::class);

        $this->assertSame(4, Prompt::query()->count());
        $this->assertSame(4, Prompt::query()->where('visibility', Prompt::VISIBILITY_PUBLIC)->count());
        $this->assertSame(3, User::query()->count());
        $this->assertTrue(User::query()->where('email', 'owner@example.com')->firstOrFail()->hasRole(PlatformRoleService::ADMIN));
        $this->assertTrue(User::query()->where('email', 'moderator@example.com')->firstOrFail()->hasRole(PlatformRoleService::MODERATOR));
        $this->assertTrue(User::query()->where('email', 'library@promptforge.invalid')->firstOrFail()->hasVerifiedEmail());
        $this->assertTrue(Hash::check('a-strong-private-admin-secret', User::query()->where('email', 'owner@example.com')->firstOrFail()->password));
    }

    public function test_production_demo_seeder_refuses_to_promote_an_existing_user(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);
        config(['demo.staff.admin' => ['email' => 'owner@example.com', 'password' => 'a-strong-private-admin-secret']]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('refusing to promote');

        $this->seed(\Database\Seeders\DemoDeploymentSeeder::class);
    }
}
