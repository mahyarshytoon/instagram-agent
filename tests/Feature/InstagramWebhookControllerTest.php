<?php

namespace Tests\Feature;

use App\Jobs\ValidateCommentJob;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class InstagramWebhookControllerTest extends TestCase
{
    use DatabaseMigrations;

    private function makeSignature(string $payload, string $secret = 'test_secret'): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }

    // ... تست‌های GET بدون تغییر ...

    public function test_post_webhook_processes_comment(): void
    {
        $this->withoutMiddleware();
        Bus::fake();   // ← اینجا عوض شد

        config([
            'agent.instagram.app_secret'   => 'test_secret',
            'agent.instagram.verify_token' => 'test_token',
        ]);

        $payload = [
            'object' => 'instagram',
            'entry'  => [[
                'id'      => 'page_1',
                'changes' => [[
                    'field' => 'comments',
                    'value' => [
                        'id'   => 'comment_xyz',
                        'text' => 'سلام',
                        'from' => ['id' => 'user_1', 'username' => 'ali'],
                        'media' => ['id' => 'post_1'],
                    ],
                ]],
            ]],
        ];

        $json = json_encode($payload);
        $signature = $this->makeSignature($json);

        $response = $this->postJson('/api/instagram/webhook', $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertSee('EVENT_RECEIVED');

        $this->assertDatabaseHas('comments', [
            'instagram_comment_id' => 'comment_xyz',
        ]);

        Bus::assertDispatched(ValidateCommentJob::class);   // ← اینجا عوض شد
    }

    public function test_post_webhook_rejects_invalid_signature(): void
    {
        Bus::fake();
        config(['agent.instagram.app_secret' => 'test_secret']);

        // ... بدون تغییر ...

        Bus::assertNotDispatched(ValidateCommentJob::class);
    }

    // ... بقیه بدون تغییر ...
}