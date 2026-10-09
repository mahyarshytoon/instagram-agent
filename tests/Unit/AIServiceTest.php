<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Product;
use App\Services\AIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'agent.ai.api_base'    => 'https://api.openai.com/v1',
            'agent.ai.api_key'     => 'test_key',
            'agent.ai.model'       => 'gpt-4o-mini',
            'agent.ai.max_tokens'  => 150,
            'agent.ai.temperature' => 0.7,
            'agent.ai.timeout'     => 5,
        ]);
    }

    /** @test */
    public function it_generates_response(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'model'   => 'gpt-4o-mini',
                'choices' => [
                    ['message' => ['content' => 'قیمت ۲۰۰ هزار تومنه']],
                ],
                'usage' => ['total_tokens' => 50],
            ], 200),
        ]);

        $comment = new Comment([
            'text'           => 'قیمتش چنده؟',
            'sanitized_text' => 'قیمتش چنده؟',
        ]);

        $service = new AIService();
        $result = $service->generateReply($comment);

        $this->assertTrue($result['success']);
        $this->assertEquals('قیمت ۲۰۰ هزار تومنه', $result['text']);
        $this->assertEquals(50, $result['tokens']);
        $this->assertEquals('gpt-4o-mini', $result['model']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function it_returns_failure_on_api_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => ['message' => 'API error'],
            ], 500),
        ]);

        $comment = new Comment([
            'sanitized_text' => 'سلام',
        ]);

        $service = new AIService();
        $result = $service->generateReply($comment);

        $this->assertFalse($result['success']);
        $this->assertEquals('', $result['text']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function it_includes_product_context_in_system_prompt(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'model'   => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'بله']]],
                'usage'   => ['total_tokens' => 10],
            ], 200),
        ]);

        $comment = new Comment(['sanitized_text' => 'سلام']);

        $product = new Product([
            'name'  => 'کفش پوما',
            'price' => 2000000,
        ]);

        $service = new AIService();
        $service->generateReply($comment, $product);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['messages'][0]['content'], 'کفش پوما');
        });
    }

    /** @test */
    public function it_includes_history_in_messages(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'model'   => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'بله']]],
                'usage'   => ['total_tokens' => 10],
            ], 200),
        ]);

        $comment = new Comment(['sanitized_text' => 'قیمت چنده؟']);

        $history = [
            ['role' => 'user', 'content' => 'سلام'],
            ['role' => 'assistant', 'content' => 'سلام! چطور کمک کنم؟'],
        ];

        $service = new AIService();
        $service->generateReply($comment, null, $history);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return count($body['messages']) === 4
                && $body['messages'][1]['content'] === 'سلام'
                && $body['messages'][2]['content'] === 'سلام! چطور کمک کنم؟'
                && $body['messages'][3]['content'] === 'قیمت چنده؟';
        });
    }

    /** @test */
    public function it_handles_network_error_gracefully(): void
    {
        Http::fake([
            'api.openai.com/*' => function () {
                throw new ConnectionException('Network failed');
            },
        ]);

        $comment = new Comment(['sanitized_text' => 'سلام']);

        $service = new AIService();
        $result = $service->generateReply($comment);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection', $result['error']);
    }
}