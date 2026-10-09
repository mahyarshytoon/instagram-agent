<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\InstagramUser;

class UserAnalyzer
{
    /**
     * تحلیل کاربر و بروزرسانی امتیاز
     *
     * @param InstagramUser $user
     * @return array{score: int, level: string, total_comments: int, spam_comments: int}
     */
    public function analyze(InstagramUser $user): array
    {
        $score = 50; // شروع از وسط

        $stats = $this->getStats($user);

        // ۱. کاربر قدیمی؟
        if ($user->created_at->diffInDays(now()) > 30) {
            $score += 10;
        }

        // ۲. فالوور ماست؟
        if ($user->is_follower) {
            $score += 15;
        }

        // ۳. کامنت‌های قبول‌شده
        $acceptedRatio = $stats['total_comments'] > 0
            ? $stats['accepted_comments'] / $stats['total_comments']
            : 0;
        $score += (int) ($acceptedRatio * 20);

        // ۴. اسپم‌های قبلی
        if ($stats['spam_comments'] > 0) {
            $score -= $stats['spam_comments'] * 10;
        }

        // ۵. کامنت زیاد
        if ($stats['total_comments'] > 10) {
            $score += 10;
        }

        // محدود به ۰ تا ۱۰۰
        $score = max(0, min(100, $score));

        // سطح کاربر
        $level = match(true) {
            $score >= 70 => 'trusted',
            $score >= 40 => 'normal',
            $score >= 20 => 'suspicious',
            default      => 'blocked',
        };

        // ذخیره تو دیتابیس
        $user->update([
            'score'          => $score,
            'total_comments' => $stats['total_comments'],
            'last_seen_at'   => now(),
        ]);

        return [
            'score'          => $score,
            'level'          => $level,
            'total_comments' => $stats['total_comments'],
            'spam_comments'  => $stats['spam_comments'],
        ];
    }

    /**
     * جمع آوری آمار کاربر
     */
    private function getStats(InstagramUser $user): array
    {
        $total = Comment::where('instagram_user_id', $user->id)->count();
        $spam = Comment::where('instagram_user_id', $user->id)
            ->where('is_spam', true)
            ->count();
        $accepted = Comment::where('instagram_user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        return [
            'total_comments'    => $total,
            'spam_comments'     => $spam,
            'accepted_comments' => $accepted,
        ];
    }
}