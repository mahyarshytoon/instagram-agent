<?php
// app/Services/SpamDetector.php

namespace App\Services;

use App\Models\AdminSetting;

class SpamDetector
{
    /**
     * بررسی اسپم بودن کامنت
     *
     * @param string $text متن پاک‌شده کامنت
     * @return array{is_spam: bool, score: int, reasons: array}
     */
    public function analyze(string $text): array
    {
        $score = 0;
        $reasons = [];

        // ۱. کلمات ممنوعه (substring)
        if ($this->hasBannedWords($text)) {
            $score += 50;
            $reasons[] = 'banned_words';
        }

        // ۲. الگوهای ممنوعه (regex — فحش‌ها و لینک)
        if ($this->hasBannedPatterns($text)) {
            $score += 60;
            $reasons[] = 'banned_patterns';
        }

        // ۳. لینک
        if ($this->hasLink($text)) {
            $score += 30;
            $reasons[] = 'contains_link';
        }

        // ۴. کاراکتر تکراری
        if ($this->hasExcessiveRepetition($text)) {
            $score += 20;
            $reasons[] = 'excessive_repetition';
        }

        // ۵. حروف بزرگ زیاد (انگلیسی)
        if ($this->hasExcessiveCaps($text)) {
            $score += 15;
            $reasons[] = 'excessive_caps';
        }

        // ۶. طول
        if (! $this->hasValidLength($text)) {
            $score += 25;
            $reasons[] = 'invalid_length';
        }

        // اگه امتیاز >= 50 باشه، اسپمه
        return [
            'is_spam' => $score >= 50,
            'score'   => min($score, 100),
            'reasons' => $reasons,
        ];
    }

    /**
     * بررسی کلمات ممنوعه (substring)
     * 
     * مقدار از admin_settings خونده می‌شه (قابل ویرایش از پنل).
     * اگه نبود، از config/agent.php به عنوان fallback.
     */
    private function hasBannedWords(string $text): bool
    {
        $bannedWords = AdminSetting::get(
            'spam.banned_words',
            config('agent.spam.banned_words', [])
        );

        if (empty($bannedWords) || ! is_array($bannedWords)) {
            return false;
        }

        $lowerText = mb_strtolower($text, 'UTF-8');

        foreach ($bannedWords as $word) {
            $word = mb_strtolower((string) $word, 'UTF-8');
            if ($word !== '' && mb_strpos($lowerText, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * بررسی الگوهای ممنوعه (regex)
     * 
     * مقدار از admin_settings خونده می‌شه (قابل ویرایش از پنل).
     */
    private function hasBannedPatterns(string $text): bool
    {
        $patterns = AdminSetting::get(
            'spam.banned_patterns',
            config('agent.spam.banned_patterns', [])
        );

        if (empty($patterns) || ! is_array($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            // @ برای سرکوب خطای regex نامعتبر
            if (@preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * بررسی وجود لینک
     */
    private function hasLink(string $text): bool
    {
        $pattern = '/(https?:\/\/|www\.|[a-z0-9-]+\.(com|ir|net|org|io))/i';
        return preg_match($pattern, $text) === 1;
    }

    /**
     * بررسی تکرار زیاد کاراکتر
     * مثلا: "!!!!!!!!" یا "؟؟؟؟؟؟"
     */
    private function hasExcessiveRepetition(string $text): bool
    {
        return preg_match('/(.)\1{4,}/u', $text) === 1;
    }

    /**
     * بررسی حروف بزرگ زیاد (فقط انگلیسی)
     */
    private function hasExcessiveCaps(string $text): bool
    {
        $letters = preg_replace('/[^a-zA-Z]/', '', $text);

        if (mb_strlen($letters) < 10) {
            return false;
        }

        $upperCount = preg_match_all('/[A-Z]/', $letters);
        $ratio = $upperCount / mb_strlen($letters);

        return $ratio > 0.7;
    }

    /**
     * بررسی طول متن
     * 
     * min/max از admin_settings خونده می‌شه.
     */
    private function hasValidLength(string $text): bool
    {
        $length = mb_strlen($text, 'UTF-8');

        $min = (int) AdminSetting::get(
            'spam.min_length',
            config('agent.spam.min_length', 2)
        );

        $max = (int) AdminSetting::get(
            'spam.max_length',
            config('agent.spam.max_length', 500)
        );

        return $length >= $min && $length <= $max;
    }
}