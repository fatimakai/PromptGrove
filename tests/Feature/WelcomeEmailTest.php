<?php

namespace Tests\Feature;

use App\Jobs\SendWelcomeEmailJob;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_queues_a_welcome_email_job(): void
    {
        Queue::fake();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        Queue::assertPushed(SendWelcomeEmailJob::class, fn ($job) => $job->user->email === 'test@example.com');
    }

    public function test_welcome_email_has_recipient_subject_and_content(): void
    {
        Mail::fake();
        $user = User::factory()->create(['name' => 'Alice Smith', 'email' => 'alice@example.com']);

        (new SendWelcomeEmailJob($user))->handle();

        Mail::assertSent(WelcomeMail::class, fn ($mail) => $mail->hasTo('alice@example.com') && $mail->envelope()->subject === 'Welcome to PromptGrove'
        );
        $this->assertStringContainsString('Alice Smith', (new WelcomeMail($user))->render());
    }
}
