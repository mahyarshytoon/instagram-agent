<?php
// app/Http/Middleware/AdminAuth.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * دربان پنل ادمین
     * 
     * چک می‌کنه:
     *   1. session ادمین وجود داره؟
     *   2. session منقضی نشده؟
     *   3. IP مشکوک نیست؟ (اختیاری)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = config('admin.auth.session_key', 'admin_authenticated_at');
        $ttl        = (int) config('admin.auth.session_ttl', 120); // دقیقه

        // ═══ چک ۱: session ادمین هست؟ ═══
        $authenticatedAt = $request->session()->get($sessionKey);

        if (!$authenticatedAt) {
            return $this->redirectToLogin($request, 'no_session');
        }

        // ═══ چک ۲: session منقضی نشده؟ ═══
        $ageInMinutes = now()->diffInMinutes($authenticatedAt);

        if ($ageInMinutes > $ttl) {
            $request->session()->forget($sessionKey);
            return $this->redirectToLogin($request, 'session_expired');
        }

        // ═══ چک ۳: Session رو refresh کن (sliding expiration) ═══
        // هر بار که request میاد، زمان session رو تازه کن
        // اینطوری اگه فعال باشی، logout نمی‌شی
        $request->session()->put($sessionKey, now());

        // ═══ چک ۴: IP رو ذخیره کن (برای audit) ═══
        $request->session()->put('admin_ip', $request->ip());

        return $next($request);
    }

    /**
     * ریدایرکت به صفحه لاگین با پیام مناسب
     */
    private function redirectToLogin(Request $request, string $reason): Response
    {
        // اگه AJAX بود، JSON برگردون
        if ($request->expectsJson()) {
            return response()->json([
                'error'  => 'unauthorized',
                'reason' => $reason,
            ], 401);
        }

        // لاگ برای امنیت
        Log::warning('Admin auth failed', [
            'reason' => $reason,
            'ip'     => $request->ip(),
            'url'    => $request->fullUrl(),
        ]);

        $loginPath = config('admin.route.prefix') . '/' . config('admin.route.login_path');

        return redirect()
            ->to('/' . $loginPath)
            ->with('auth_error', $reason);
    }
}