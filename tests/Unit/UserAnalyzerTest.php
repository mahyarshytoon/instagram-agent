<?php

namespace Tests\Unit;

use App\Models\InstagramUser;
use App\Services\UserAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_analyzes_new_user(): void
    {
        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'test',
        ]);

        $analyzer = new UserAnalyzer();
        $result = $analyzer->analyze($user);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
    }

    public function test_follower_gets_higher_score(): void
    {
        $user1 = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'a',
            'is_follower'  => false,
        ]);
        $user2 = InstagramUser::create([
            'instagram_id' => '2',
            'username'     => 'b',
            'is_follower'  => true,
        ]);

        $analyzer = new UserAnalyzer();
        $r1 = $analyzer->analyze($user1);
        $r2 = $analyzer->analyze($user2);

        $this->assertGreaterThan($r1['score'], $r2['score']);
    }
}