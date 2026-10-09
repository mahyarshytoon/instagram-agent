<?php
// app/Http/Controllers/Admin/LoginController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * نمایش فرم لاگین
     */
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    /**
     * پردازش لاگین
     */
    public function login(Request $request): RedirectResponse
    {
        // ═══ ۱. اعتبارسنجی ورودی ═══
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:1'],
        ], [
            'password.required' => 'رمز عبور را وارد کنید.',
        ]);

        // ═══ ۲. Rate Limiting (جلوگیری از brute force) ═══
        $key = 'admin_login:' . $request->ip();
        $maxAttempts = (int) config('admin.auth.max_attempts', 5);
        $lockoutMinutes = (int) config('admin.auth.lockout_minutes', 15);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            Log::warning('Admin login: too many attempts', [
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'password' => "تعداد تلاش‌های ناموفق زیاد است. لطفاً {$minutes} دقیقه دیگر تلاش کنید.",
            ]);
        }

        // ═══ ۳. چک رمز ═══
        $hash = (string) config('admin.auth.password_hash');

        if (empty($hash)) {
            Log::error('Admin login: ADMIN_PASSWORD_HASH not set in .env');

            return back()->withErrors([
                'password' => 'تنظیمات پنل ناقص است. با مدیر سیستم تماس بگیرید.',
            ]);
        }

        if (!Hash::check($validated['password'], $hash)) {
            // لاگین ناموفق
            RateLimiter::hit($key, $lockoutMinutes * 60);

            Log::warning('Admin login: wrong password', [
                'ip'     => $request->ip(),
                'remain' => RateLimiter::remaining($key, $maxAttempts),
            ]);

            $remaining = RateLimiter::remaining($key, $maxAttempts);

            return back()->withErrors([
                'password' => "رمز عبور اشتباه است. ({$remaining} تلاش باقی‌مانده)",
            ]);
        }

        // ═══ ۴. لاگین موفق ═══
        RateLimiter::clear($key);

        $sessionKey = config('admin.auth.session_key', 'admin_authenticated_at');
        $request->session()->regenerate(); // جلوگیری از session fixation
        $request->session()->put($sessionKey, now());
        $request->session()->put('admin_ip', $request->ip());

        Log::info('Admin logged in', [
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(
            '/' . config('admin.route.prefix') . '/' . config('admin.route.home_path')
        );
    }

    /**
     * خروج از پنل
     */
    public function logout(Request $request): RedirectResponse
    {
        $sessionKey = config('admin.auth.session_key', 'admin_authenticated_at');

        Log::info('Admin logged out', [
            'ip' => $request->ip(),
        ]);

        $request->session()->forget([$sessionKey, 'admin_ip']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $loginPath = config('admin.route.prefix') . '/' . config('admin.route.login_path');

        return redirect()->to('/' . $loginPath);
    }
}