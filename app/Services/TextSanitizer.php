<?php

namespace App\Services;

class TextSanitizer
{
    /**
     * پاک‌سازی متن کامنت
     */
    public function sanitize(string $text): string
    {
        // ۱. حذف تگ‌های HTML
        $text = strip_tags($text);

        // ۲. تبدیل کاراکترهای خاص HTML به متن عادی
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // ۳. حذف کاراکترهای کنترلی (به جز \n و \t)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // ۴. تبدیل فاصله‌های چندتایی به یکی
        $text = preg_replace('/\s+/u', ' ', $text);

        // ۵. حذف فاصله اول و آخر
        $text = trim($text);

        return $text;
    }

    /**
     * بررسی طول مجاز
     */
    public function isValidLength(string $text): bool
    {
        $length = mb_strlen($text, 'UTF-8');
        $min = config('agent.spam.min_length', 2);
        $max = config('agent.spam.max_length', 500);

        return $length >= $min && $length <= $max;
    }
}