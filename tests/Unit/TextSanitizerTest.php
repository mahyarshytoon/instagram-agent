<?php

namespace Tests\Unit;

use App\Services\TextSanitizer;
use Tests\TestCase;

class TextSanitizerTest extends TestCase
{
    private TextSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new TextSanitizer();
    }

    public function test_it_removes_html_tags(): void
    {
        $result = $this->sanitizer->sanitize('<script>alert("xss")</script>سلام');
        $this->assertEquals('alert("xss")سلام', $result);
    }

    public function test_it_collapses_whitespace(): void
    {
        $result = $this->sanitizer->sanitize('سلام    خوبی؟');
        $this->assertEquals('سلام خوبی؟', $result);
    }

    public function test_it_trims(): void
    {
        $result = $this->sanitizer->sanitize('  سلام  ');
        $this->assertEquals('سلام', $result);
    }

    public function test_length_validation(): void
    {
        $this->assertTrue($this->sanitizer->isValidLength('سلام'));
        $this->assertFalse($this->sanitizer->isValidLength('x'));  // کوتاه‌تر از ۲
    }

    public function test_persian_length(): void
    {
        // ۵ کاراکتر فارسی باید درست شمرده شه
        $this->assertTrue($this->sanitizer->isValidLength('سلامت'));
    }
}