<?php

namespace App\Console\Commands;

use App\Models\InstagramPost;
use App\Models\InstagramUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendFakeComment extends Command
{
    /**
     * ─────────────────────────────────────────────
     * امضای Command
     * ─────────────────────────────────────────────
     * fake:comment
     *   {text?}          → متن کامنت (اگه ندادی، interactive می‌پرسه)
     *   --user=          → instagram_id کاربر (اگه ندادی، می‌سازه)
     *   --post=          → instagram_post_id پست (اگه ندادی، می‌سازه)
     *   --url=           → آدرس webhook (پیش‌فرض: 127.0.0.1:8000)
     *   --username=      → username کاربر (اگه کاربر جدید بسازه)
     */
    protected $signature = 'fake:comment
                            {text? : متن کامنت}
                            {--user= : instagram_id کاربر}
                            {--post= : instagram_post_id پست}
                            {--username= : username کاربر (برای ساخت جدید)}
                            {--url=http://127.0.0.1:8000 : آدرس سرور webhook}';

    protected $description = 'ارسال کامنت جعلی به webhook برای تست کل flow';

    public function handle(): int
    {
        $this->info('🎭 ساخت کامنت جعلی...');
        $this->newLine();

        // ─── مرحله ۱: متن کامنت ───
        $text = $this->argument('text')
             ?? $this->ask('متن کامنت چیه؟', 'قیمت این محصول چنده؟');

        // ─── مرحله ۲: کاربر ───
        $user = $this->resolveUser();

        // ─── مرحله ۳: پست ───
        $post = $this->resolvePost();

        // ─── مرحله ۴: ساخت payload ───
        $payload = $this->buildPayload($text, $user, $post);

        // ─── مرحله ۵: محاسبه امضا ───
        $signature = $this->signPayload($payload);

        // ─── مرحله ۶: ارسال ───
        $this->sendToWebhook($payload, $signature);

        return self::SUCCESS;
    }

    /**
     * ─────────────────────────────────────────────
     * پیدا کردن یا ساختن کاربر
     * ─────────────────────────────────────────────
     */
    private function resolveUser(): InstagramUser
    {
        $instagramId = $this->option('user');

        if ($instagramId) {
            $user = InstagramUser::where('instagram_id', $instagramId)->first();

            if ($user) {
                $this->line("👤 کاربر موجود: <info>{$user->username}</info> ({$instagramId})");
                return $user;
            }

            $this->warn("⚠️  کاربر با instagram_id={$instagramId} پیدا نشد — می‌سازم.");
        }

        // اگه کاربر موجود نبود، بساز
        $username = $this->option('username')
                 ?? 'fake_user_' . Str::random(6);

        $user = InstagramUser::create([
            'instagram_id' => $instagramId ?? 'ig_fake_' . Str::random(10),
            'username'     => $username,
            'full_name'    => 'کاربر جعلی تست',
            'is_follower'  => true,
            'score'        => 50,
            'last_seen_at' => now(),
        ]);

        $this->line("👤 کاربر جدید ساخته شد: <info>{$user->username}</info> ({$user->instagram_id})");

        return $user;
    }

    /**
     * ─────────────────────────────────────────────
     * پیدا کردن یا ساختن پست
     * ─────────────────────────────────────────────
     */
    private function resolvePost(): InstagramPost
    {
        $postId = $this->option('post');

        if ($postId) {
            $post = InstagramPost::where('instagram_post_id', $postId)->first();

            if ($post) {
                $this->line("📸 پست موجود: <info>{$post->instagram_post_id}</info>");
                return $post;
            }

            $this->warn("⚠️  پست با instagram_post_id={$postId} پیدا نشد — می‌سازم.");
        }

        // اگه پست موجود نبود، بساز
        $post = InstagramPost::create([
            'instagram_post_id' => $postId ?? 'post_fake_' . Str::random(10),
            'caption'           => 'پست تستی برای fake:comment',
            'media_type'        => 'IMAGE',
            'posted_at'         => now(),
        ]);

        $this->line("📸 پست جدید ساخته شد: <info>{$post->instagram_post_id}</info>");

        return $post;
    }

    /**
     * ─────────────────────────────────────────────
     * ساخت payload شبیه Meta
     * ─────────────────────────────────────────────
     * ساختار دقیقاً همون چیزیه که WebhookHandler::extractData انتظار داره:
     *   entry[0].changes[0].field = 'comments'
     *   entry[0].changes[0].value = {id, text, from, media}
     */
    private function buildPayload(string $text, InstagramUser $user, InstagramPost $post): array
    {
        $commentId = 'comment_fake_' . Str::random(12);

        $payload = [
            'object' => 'instagram',
            'entry'  => [
                [
                    'id'      => 'ig_page_fake',
                    'time'    => now()->timestamp,
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'id'   => $commentId,
                                'text' => $text,
                                'from' => [
                                    'id'       => $user->instagram_id,
                                    'username' => $user->username,
                                ],
                                'media' => [
                                    'id'           => $post->instagram_post_id,
                                    'media_product_type' => 'FEED',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->newLine();
        $this->line('📦 Payload ساخته شد:');
        $this->line("   comment_id = <info>{$commentId}</info>");
        $this->line("   text       = <info>{$text}</info>");

        return $payload;
    }

    /**
     * ─────────────────────────────────────────────
     * محاسبه امضای HMAC-SHA256
     * ─────────────────────────────────────────────
     * دقیقاً همون چیزی که SignatureVerifier انتظار داره:
     *   'sha256=' . hash_hmac('sha256', $rawBody, $appSecret)
     */
    private function signPayload(array $payload): string
    {
        $rawBody   = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $appSecret = config('agent.instagram.app_secret');

        if (!$appSecret) {
            $this->error('❌ INSTAGRAM_APP_SECRET تو .env ست نشده!');
            $this->line('   امضا نمی‌تونه ساخته بشه و webhook ردش می‌کنه.');
            exit(self::FAILURE);
        }

        return 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
    }

    /**
     * ─────────────────────────────────────────────
     * ارسال به webhook
     * ─────────────────────────────────────────────
     */
    private function sendToWebhook(array $payload, string $signature): void
    {
        $baseUrl = rtrim($this->option('url'), '/');
        $url     = "{$baseUrl}/api/instagram/webhook";

        $this->newLine();
        $this->line("🚀 ارسال به: <info>{$url}</info>");

        try {
            // ⚠️ نکته: Http::post با آرایه، خودش json_encode می‌کنه
            //   ولی امضا رو روی raw body محاسبه کردیم.
            //   پس باید همون raw body رو دستی بفرستیم.
            $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $response = Http::withHeaders([
                'X-Hub-Signature-256' => $signature,
                'Content-Type'        => 'application/json',
            ])
            ->withBody($rawBody, 'application/json')
            ->timeout(15)
            ->post($url);

            $this->newLine();

            if ($response->successful()) {
                $this->info("✅ پاسخ سرور: {$response->status()}");
                $this->line("   body: {$response->body()}");
                $this->newLine();
                $this->info('🎉 کامنت جعلی با موفقیت ارسال شد!');
                $this->line('   برای دیدن نتیجه:');
                $this->line('   • لاگ‌ها: <info>storage/logs/laravel.log</info>');
                $this->line('   • دیتابیس: <info>php artisan tinker</info> → Comment::latest()->first()');
            } else {
                $this->error("❌ خطا: {$response->status()}");
                $this->line("   body: {$response->body()}");
            }

        } catch (\Throwable $e) {
            $this->error('❌ خطای اتصال: ' . $e->getMessage());
            $this->line('   چک کن که سرور روی ' . $this->option('url') . ' بالا باشه.');
        }
    }
}