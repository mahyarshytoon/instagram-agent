<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    /**
     * ─────────────────────────────────────────────
     * تزریق وابستگی
     * ─────────────────────────────────────────────
     * Laravel خودش WebhookHandler رو inject می‌کنه
     */
    public function __construct(
        private WebhookHandler $webhookHandler
    ) {}

    /**
     * ─────────────────────────────────────────────
     * GET /api/instagram/webhook
     * ─────────────────────────────────────────────
     * Meta وقتی می‌خواد webhook رو verify کنه، این درخواست رو می‌فرسته:
     *
     *   GET /api/instagram/webhook
     *       ?hub.mode=subscribe
     *       &hub.verify_token=MY_SECRET
     *       &hub.challenge=RANDOM_STRING
     *
     * ما باید:
     *   ۱. hub.verify_token رو با توکن تو .env چک کنیم
     *   ۲. اگه درست بود، hub.challenge رو به‌صورت plain text برگردونیم
     */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode', $request->query('hub.mode'));
        $token     = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $expectedToken = config('agent.instagram.verify_token');

        Log::info('Webhook verification attempt', [
            'mode'      => $mode,
            'token_ok'  => $token === $expectedToken,
            'has_challenge' => !empty($challenge),
        ]);

        // چک mode و توکن
        if ($mode !== 'subscribe' || $token !== $expectedToken) {
            Log::warning('Webhook verification failed', [
                'mode'  => $mode,
                'token' => $token ? substr($token, 0, 8) . '...' : null,
            ]);

            return response('Forbidden', 403);
        }

        // برگردوندن challenge به‌صورت plain text
        return response($challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * ─────────────────────────────────────────────
     * POST /api/instagram/webhook
     * ─────────────────────────────────────────────
     * Meta هر کامنت جدید رو با این درخواست می‌فرسته.
     *
     * ⚠️ نکته مهم: Meta فقط ۵ ثانیه مهلت می‌ده.
     * پس ما فوراً 200 OK می‌دیم و WebhookHandler فقط
     * کامنت رو ذخیره می‌کنه (کار AI می‌ره تو Queue).
     */
    public function handle(Request $request): Response
    {
        $startTime = microtime(true);

        Log::info('Instagram webhook received', [
            'ip'          => $request->ip(),
            'has_signature' => $request->hasHeader('X-Hub-Signature-256'),
        ]);

        try {
            $result = $this->webhookHandler->handle($request);

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::info('Instagram webhook processed', [
                'success'     => $result['success'],
                'message'     => $result['message'],
                'comment_id'  => $result['comment_id'],
                'duration_ms' => $duration,
            ]);

            // همیشه 200 OK برگردون (حتی اگه خطا باشه)
            // چرا؟ چون اگه 4xx/5xx برگردونیم، Meta دوباره تلاش می‌کنه
            // و ما می‌خوایم خودمون کنترل کنیم چی ذخیره بشه
            return response('EVENT_RECEIVED', 200)
                ->header('Content-Type', 'text/plain');

        } catch (\Throwable $e) {
            Log::error('Instagram webhook exception', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            // بازم 200 برمی‌گردونیم تا Meta retry نکنه
            return response('EVENT_RECEIVED', 200)
                ->header('Content-Type', 'text/plain');
        }
    }
}