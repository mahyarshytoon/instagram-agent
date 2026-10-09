<?php

namespace Tests\Unit;

use App\Services\SpamDetector;
use Tests\TestCase;

class SpamDetectorTest extends TestCase
{
    private SpamDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new SpamDetector();
    }

    public function test_it_detects_banned_words(): void
    {
        $result = $this->detector->analyze('buy followers now crypto');
        $this->assertTrue($result['is_spam']);
        $this->assertContains('banned_words', $result['reasons']);
    }

    public function test_it_detects_links(): void
    {
        $result = $this->detector->analyze('check this https://spam.com');
        $this->assertContains('contains_link', $result['reasons']);
    }

    public function test_it_detects_excessive_repetition(): void
    {
        $result = $this->detector->analyze('سلام!!!!!!!!!!!');
        $this->assertContains('excessive_repetition', $result['reasons']);
    }

    public function test_it_accepts_normal_comment(): void
    {
        $result = $this->detector->analyze('سلام قیمتش چنده؟');
        $this->assertFalse($result['is_spam']);
    }

    public function test_it_rejects_too_short_comment(): void
    {
        $result = $this->detector->analyze('x');
        $this->assertContains('invalid_length', $result['reasons']);
    }
}