<?php

namespace App\Services;
use App\Models\Setting;
use App\Models\Comment;
use App\Models\InstagramUser;
use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    /**
     * بررسی اینکه کاربر مجاز به کامنت گذاشتنه یا نه
     *
     * @param InstagramUser $user
     * @return array{allowed: bool, reason: string|null, retry_after: int}
     */
    public function check(InstagramUser $user): array
    {
        // ۱. چک کلی (کل سیستم)
        if (!$this->checkGlobal()) {
            return [
                'allowed'     => false,
                'reason'      => 'global_limit',
                'retry_after' => 60,
            ];
        }

        // ۲. چک کاربر
        if (!$this->checkUser($user)) {
            return [
                'allowed'     => false,
                'reason'      => 'user_limit',
                'retry_after' => 3600,
            ];
        }

        return [
            'allowed'     => true,
            'reason'      => null,
            'retry_after' => 0,
        ];
    }

    /**
     * محدودیت کلی — چند کامنت در دقیقه
     */
    private function checkGlobal(): bool
    {
        $maxPerMinute = (int) (Setting::get('rate_limit.max_per_minute')
            ?? config('agent.rate_limit.max_per_minute', 10));

        $key = 'rate_limit:global:' . now()->format('Y-m-d-H-i');
        $count = Cache::get($key, 0);

        if ($count >= $maxPerMinute) {
            return false;
        }

        Cache::put($key, $count + 1, 60);
        return true;
    }

    /**
     * محدودیت کاربر — چند کامنت در ساعت
     */
    private function checkUser(InstagramUser $user): bool
    {
        $maxPerHour = (int) (Setting::get('rate_limit.max_per_user_per_hour')
            ?? config('agent.rate_limit.max_per_user_per_hour', 5));

        $key = "rate_limit:user:{$user->id}:" . now()->format('Y-m-d-H');
        $count = Cache::get($key, 0);

        if ($count >= $maxPerHour) {
            return false;
        }

        Cache::put($key, $count + 1, 3600);
        return true;
    }
}