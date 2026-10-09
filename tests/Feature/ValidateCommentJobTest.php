<?php

namespace Tests\Feature;

use App\Exceptions\InstagramApiException;
use App\Jobs\ValidateCommentJob;
use App\Models\Comment;
use App\Models\InstagramPost;
use App\Models\InstagramUser;
use App\Models\Product;
use App\Models\Response;
use App\Services\AIService;
use App\Services\ConversationManager;
use App\Services\InstagramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ValidateCommentJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('agent.instagram.api_base', 'https://graph.instagram.com');
        config()->set('agent.instagram.access_token', 'test-token-123');
        config()->set('agent.instagram.timeout', 5);

        config()->set('agent.ai.api_base', 'https://api.openai.com/v1');
        config()->set('agent.ai.api_key', 'test-openai-key');
        config()->set('agent.ai.model', 'gpt-4o-mini');
        config()->set('agent.ai.timeout', 5);
    }

    public function test_it_processes_a_comment_end_to_end_successfully(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'id'      => 'chatcmpl-test',
                'model'   => 'gpt-4o-mini',
                'choices' => [[
                    'message' => [
                        'role'    => 'assistant',
                        'content' => 'سلام! قیمت این محصول ۱۵۰,۰۰۰ تومانه.',
                    ],
                ]],
                'usage' => ['total_tokens' => 50],
            ], 200),

            'graph.instagram.com/*' => Http::response([
                'id' => 'reply_abc123',
            ], 200),
        ]);

        $user = InstagramUser::create([
            'instagram_id' => 'user_123',
            'username'     => 'test_user',
        ]);

        $post = InstagramPost::create([
            'instagram_post_id' => 'post_456',
            'caption'           => 'محصول جدید',
        ]);

        $product = Product::create([
            'name'      => 'محصول تست',
            'price'     => 150000,
            'stock'     => 10,
            'is_active' => true,
        ]);

        $post->products()->attach($product->id);

        $comment = Comment::create([
            'instagram_comment_id' => 'comment_789',
            'instagram_post_id'    => $post->id,
            'instagram_user_id'    => $user->id,
            'text'                 => 'قیمت چنده؟',
            'sanitized_text'       => 'قیمت چنده؟',
            'status'               => 'pending',
        ]);

        (new ValidateCommentJob($comment->id))->handle(
            app(AIService::class),
            app(InstagramService::class),
            app(ConversationManager::class),
        );

        $comment->refresh();
        $this->assertEquals('processed', $comment->status);
        $this->assertNotNull($comment->processed_at);

        $this->assertDatabaseHas('responses', [
            'comment_id'         => $comment->id,
            'type'               => 'ai',
            'status'             => 'sent',
            'instagram_reply_id' => 'reply_abc123',
        ]);

        $response = Response::where('comment_id', $comment->id)->first();
        $this->assertStringContainsString('۱۵۰,۰۰۰', $response->content);

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), 'api.openai.com')) {
                return $request->hasHeader('Authorization', 'Bearer test-openai-key')
                    && isset($request['messages']);
            }

            if (str_contains($request->url(), 'graph.instagram.com')) {
                return str_contains($request->url(), 'comment_789/replies')
                    && $request['message'] === 'سلام! قیمت این محصول ۱۵۰,۰۰۰ تومانه.';
            }

            return false;
        });
    }

    public function test_it_handles_instagram_auth_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'پاسخ تستی']]],
                'model'   => 'gpt-4o-mini',
                'usage'   => ['total_tokens' => 20],
            ], 200),

            'graph.instagram.com/*' => Http::response([
                'error' => ['message' => 'Invalid OAuth access token'],
            ], 401),
        ]);

        [$comment] = $this->createTestData();

        (new ValidateCommentJob($comment->id))->handle(
            app(AIService::class),
            app(InstagramService::class),
            app(ConversationManager::class),
        );

        $comment->refresh();

        $this->assertEquals('failed_auth', $comment->status);
        $this->assertDatabaseMissing('responses', [
            'comment_id' => $comment->id,
        ]);
    }

    public function test_it_throws_on_instagram_rate_limit_for_retry(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'پاسخ']]],
                'model'   => 'gpt-4o-mini',
                'usage'   => ['total_tokens' => 20],
            ], 200),

            'graph.instagram.com/*' => Http::response([
                'error' => ['message' => 'Rate limit exceeded'],
            ], 429),
        ]);

        [$comment] = $this->createTestData();

        $this->expectException(InstagramApiException::class);

        try {
            (new ValidateCommentJob($comment->id))->handle(
                app(AIService::class),
                app(InstagramService::class),
                app(ConversationManager::class),
            );
        } finally {
            $comment->refresh();
            $this->assertEquals('failed', $comment->status);
        }
    }

    public function test_it_handles_ai_failure(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => ['message' => 'Rate limit exceeded'],
            ], 429),

            'graph.instagram.com/*' => Http::response(['id' => 'should-not-be-called'], 200),
        ]);

        [$comment] = $this->createTestData();

        $this->expectException(\RuntimeException::class);

        (new ValidateCommentJob($comment->id))->handle(
            app(AIService::class),
            app(InstagramService::class),
            app(ConversationManager::class),
        );
    }

    public function test_it_skips_comment_when_no_product_attached(): void
    {
        Http::fake();

        $user = InstagramUser::create(['instagram_id' => 'u1', 'username' => 'u']);
        $post = InstagramPost::create(['instagram_post_id' => 'p1']);

        $comment = Comment::create([
            'instagram_comment_id' => 'c1',
            'instagram_post_id'    => $post->id,
            'instagram_user_id'    => $user->id,
            'text'                 => 'تست',
            'sanitized_text'       => 'تست',
            'status'               => 'pending',
        ]);

        (new ValidateCommentJob($comment->id))->handle(
            app(AIService::class),
            app(InstagramService::class),
            app(ConversationManager::class),
        );

        $comment->refresh();
        $this->assertEquals('skipped', $comment->status);

        Http::assertNothingSent();
    }

    public function test_it_is_idempotent_for_processed_comments(): void
    {
        Http::fake();

        [$comment] = $this->createTestData();
        $comment->update(['status' => 'processed']);

        (new ValidateCommentJob($comment->id))->handle(
            app(AIService::class),
            app(InstagramService::class),
            app(ConversationManager::class),
        );

        Http::assertNothingSent();
    }

    protected function createTestData(): array
    {
        $user = InstagramUser::create([
            'instagram_id' => 'user_' . uniqid(),
            'username'     => 'test_user',
        ]);

        $post = InstagramPost::create([
            'instagram_post_id' => 'post_' . uniqid(),
        ]);

        $product = Product::create([
            'name'      => 'محصول تست',
            'price'     => 100000,
            'stock'     => 5,
            'is_active' => true,
        ]);

        $post->products()->attach($product->id);

        $comment = Comment::create([
            'instagram_comment_id' => 'comment_' . uniqid(),
            'instagram_post_id'    => $post->id,
            'instagram_user_id'    => $user->id,
            'text'                 => 'قیمت چنده؟',
            'sanitized_text'       => 'قیمت چنده؟',
            'status'               => 'pending',
        ]);

        return [$comment, $user, $post, $product];
    }
}