<?php

namespace App\Services;

use Illuminate\Http\Request;

class SignatureVerifier
{
    /**
     * بررسی امضای درخواست اینستاگرام
     */
    public function verify(Request $request): bool
    {
        // ۱. امضای اینستاگرام رو از هدر بگیر
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        // ۲. بدنه خام درخواست رو بگیر
        $payload = $request->getContent();

        // ۳. App Secret رو از config بگیر
        $appSecret = config('agent.instagram.app_secret');

        if (!$appSecret) {
            return false;
        }

        // ۴. امضای مورد انتظار رو محاسبه کن
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        // ۵. مقایسه کن (hash_equals برای جلوگیری از timing attack)
        return hash_equals($expected, $signature);
    }
}