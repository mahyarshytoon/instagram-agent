<?php

namespace Tests\Unit;

use App\Models\InstagramUser;
use App\Services\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimiterTest extends TestCase
{
    use RefreshDatabase;

    private RateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = new RateLimiter();
        Cache::flush();
    }

    public function test_it_allows_first_comment(): void
    {
        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'test',
        ]);

        $result = $this->limiter->check($user);
        $this->assertTrue($result['allowed']);
    }

    public function test_it_blocks_after_user_limit(): void
    {
        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'test',
        ]);

        // ۵ بار کامنت
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check($user);
        }

        // بار ششم باید مسدود شه
        $result = $this->limiter->check($user);
        $this->assertFalse($result['allowed']);
        $this->assertEquals('user_limit', $result['reason']);
    }
}