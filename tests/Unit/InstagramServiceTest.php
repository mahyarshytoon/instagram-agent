<?php

namespace Tests\Unit;

use App\Exceptions\InstagramApiException;
use App\Services\InstagramService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstagramServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'agent.instagram.api_base'     => 'https://graph.facebook.com',
            'agent.instagram.api_version'  => 'v21.0',
            'agent.instagram.access_token' => 'test_token',
            'agent.instagram.timeout'      => 5,
        ]);
    }

    /** @test */
    public function it_replies_successfully(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'reply_123'], 200),
        ]);

        $service = new InstagramService();
        $result = $service->replyToComment('comment_123', 'سلام');

        $this->assertTrue($result['success']);
        $this->assertEquals('reply_123', $result['reply_id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'comment_123/replies')
                && $request['message'] === 'سلام';
        });
    }

    /** @test */
    public function it_throws_on_auth_error(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Invalid token'],
            ], 401),
        ]);

        $service = new InstagramService();

        $this->expectException(InstagramApiException::class);

        try {
            $service->replyToComment('comment_123', 'سلام');
        } catch (InstagramApiException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            $this->assertTrue($e->isAuthError());
            $this->assertFalse($e->isRetryable());
            throw $e;
        }
    }

    /** @test */
    public function it_throws_on_rate_limit(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Rate limit'],
            ], 429),
        ]);

        $service = new InstagramService();

        $this->expectException(InstagramApiException::class);

        try {
            $service->replyToComment('comment_123', 'سلام');
        } catch (InstagramApiException $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertTrue($e->isRetryable());
            $this->assertFalse($e->isAuthError());
            throw $e;
        }
    }

    /** @test */
    public function it_throws_on_not_found(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);

        $service = new InstagramService();

        $this->expectException(InstagramApiException::class);

        try {
            $service->replyToComment('comment_123', 'سلام');
        } catch (InstagramApiException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertTrue($e->isPermanent());
            throw $e;
        }
    }

    /** @test */
    public function it_throws_on_network_error(): void
    {
        Http::fake([
            'graph.facebook.com/*' => function () {
                throw new ConnectionException('Connection failed');
            },
        ]);

        $service = new InstagramService();

        $this->expectException(InstagramApiException::class);

        try {
            $service->replyToComment('comment_123', 'سلام');
        } catch (InstagramApiException $e) {
            $this->assertEquals(0, $e->getStatusCode());
            $this->assertTrue($e->isRetryable());
            throw $e;
        }
    }

    /** @test */
    public function it_gets_post_info(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id'      => 'post_123',
                'caption' => 'کفش پوما',
            ], 200),
        ]);

        $service = new InstagramService();
        $result = $service->getPost('post_123');

        $this->assertEquals('کفش پوما', $result['caption']);
    }
}