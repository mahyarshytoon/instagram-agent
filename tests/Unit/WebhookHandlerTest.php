<?php

namespace Tests\Unit;
use App\Jobs\ValidateCommentJob;
use App\Models\Comment;
use App\Models\InstagramUser;
use App\Models\WebhookLog;
use App\Services\WebhookHandler;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookHandlerTest extends TestCase
{
    use DatabaseMigrations;

    private function makeValidRequest(): Request
    {
        config(['agent.instagram.app_secret' => 'test_secret']);

        $payload = [
            'object' => 'instagram',
            'entry'  => [[
                'id'      => 'page_1',
                'changes' => [[
                    'field' => 'comments',
                    'value' => [
                        'id'   => 'comment_123',
                        'text' => 'سلام قیمتش چنده؟',
                        'from' => [
                            'id'       => 'user_1',
                            'username' => 'ali',
                        ],
                        'media' => ['id' => 'post_10'],
                    ],
                ]],
            ]],
        ];

        $json = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $json, 'test_secret');

        $request = Request::create(
            '/webhook',
            'POST',
            [],
            [],
            [],
            [],
            $json
        );
        $request->headers->set('X-Hub-Signature-256', $signature);
        $request->headers->set('Content-Type', 'application/json');

        return $request;
    }

    public function test_it_rejects_invalid_signature(): void
    {
        $request = Request::create('/webhook', 'POST', [], [], [], [], '{}');
        $request->headers->set('X-Hub-Signature-256', 'sha256=wrong');

        $handler = app(WebhookHandler::class);
        $result = $handler->handle($request);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid signature', $result['message']);
    }

   public function test_it_processes_valid_comment(): void
{
    Queue::fake();

    $request = $this->makeValidRequest();
    $handler = app(WebhookHandler::class);
    $result = $handler->handle($request);

    $this->assertTrue($result['success']);
    $this->assertDatabaseHas('comments', [
        'instagram_comment_id' => 'comment_123',
        'is_spam'              => false,
    ]);

    Queue::assertPushed(ValidateCommentJob::class);
}

    public function test_it_rejects_spam_comment(): void
    {
        Queue::fake();

        config(['agent.instagram.app_secret' => 'test_secret']);

        $payload = [
            'object' => 'instagram',
            'entry'  => [[
                'changes' => [[
                    'field' => 'comments',
                    'value' => [
                        'id'   => 'comment_spam',
                        'text' => 'buy followers now! click here',
                        'from' => ['id' => 'user_spam', 'username' => 'spammer'],
                        'media' => ['id' => 'post_10'],
                    ],
                ]],
            ]],
        ];

        $json = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $json, 'test_secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $json);
        $request->headers->set('X-Hub-Signature-256', $signature);
        $request->headers->set('Content-Type', 'application/json');

        $handler = app(WebhookHandler::class);
        $result = $handler->handle($request);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('comments', [
            'instagram_comment_id' => 'comment_spam',
            'is_spam'              => true,
        ]);

        Queue::assertNotPushed(\App\Jobs\ValidateCommentJob::class);
    }

    public function test_it_creates_webhook_log(): void
    {
        Queue::fake();

        $request = $this->makeValidRequest();
        $handler = app(WebhookHandler::class);
        $handler->handle($request);

        $this->assertDatabaseCount('webhook_logs', 1);
        $this->assertDatabaseHas('webhook_logs', [
            'signature_valid' => true,
            'processed'       => true,
        ]);
    }

    // ← این متد را حذف کن اگه دیگه لازم نداری
    // public function test_debug_spam_detection(): void
    // {
    //     $spamDetector = app(\App\Services\SpamDetector::class);
    //     $result = $spamDetector->analyze('buy followers now! click here');
    //     dump($result);
    //     $this->assertTrue($result['is_spam']);
    // }
}