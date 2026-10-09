<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\InstagramPost;
use App\Models\InstagramUser;
use App\Models\WebhookLog;
use App\Jobs\ValidateCommentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookHandler
{
    /**
     * ─────────────────────────────────────────────
     * تزریق وابستگی (Dependency Injection)
     * ─────────────────────────────────────────────
     */
    public function __construct(
        private SignatureVerifier   $signatureVerifier,   // چک امضا HMAC
        private TextSanitizer       $sanitizer,           // پاک‌سازی متن
        private SpamDetector        $spamDetector,        // تشخیص اسپم
        private RateLimiter         $rateLimiter,         // کنترل نرخ
        private UserAnalyzer        $userAnalyzer,        // تحلیل کاربر
        private ConversationManager $conversationManager, // مدیریت مکالمه
    ) {}

    /**
     * ─────────────────────────────────────────────
     * نقطه ورود اصلی (Main Entry Point)
     * ─────────────────────────────────────────────
     */
    public function handle(Request $request): array
    {
        // ─── مرحله ۰: لاگ کردن webhook ───
        $log = WebhookLog::create([
            'event_type'      => 'comment',
            'payload'         => $request->all(),
            'signature_valid' => false,
            'processed'       => false,
            'ip_address'      => $request->ip(),
        ]);

        // ─── مرحله ۱: بررسی امضا ───
        if (!$this->signatureVerifier->verify($request)) {
            Log::warning('Webhook signature invalid', ['ip' => $request->ip()]);
            $log->update(['error' => 'Invalid signature']);

            return [
                'success'    => false,
                'message'    => 'Invalid signature',
                'comment_id' => null,
            ];
        }

        $log->update(['signature_valid' => true]);

        // ─── مرحله ۲: استخراج اطلاعات ───
        $data = $this->extractData($request->all());

        if (!$data) {
            $log->update(['error' => 'Invalid payload']);

            return [
                'success'    => false,
                'message'    => 'Invalid payload',
                'comment_id' => null,
            ];
        }

        // ─── مرحله ۳: پردازش در تراکنش ───
        try {
            $comment = DB::transaction(function () use ($data) {
                return $this->processComment($data);
            });

            // ═══════════════════════════════════════════
            // 🎯 مرحله ۱۱ (اصلاح‌شده): dispatch بعد از تراکنش
            // ═══════════════════════════════════════════
            // چرا بیرون از تراکنش؟
            //  ۱. اگه تراکنش rollback بشه، Job اشتباهاً dispatch نمی‌شه
            //  ۲. Queue::fake() تو تست درست می‌گیردش
            //  ۳. کامنت قبل از Job کامیت شده → Job همیشه کامنت رو می‌بینه
            // ═══════════════════════════════════════════
            if ($comment && $comment->status === 'pending') {
                ValidateCommentJob::dispatch($comment->id)
                    ->onQueue('ai-responses');
            }

            $log->update(['processed' => true]);

            return [
                'success'    => true,
                'message'    => 'Comment processed',
                'comment_id' => $comment?->id,
            ];
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            dump([
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            $log->update(['error' => $e->getMessage()]);

            return [
                'success'    => false,
                'message'    => 'Processing failed: ' . $e->getMessage(),
                'comment_id' => null,
            ];
        }
    }

    /**
     * ─────────────────────────────────────────────
     * استخراج داده‌های کلیدی از payload اینستاگرام
     * ─────────────────────────────────────────────
     */
    private function extractData(array $payload): ?array
    {
        $entry = $payload['entry'][0] ?? null;
        if (!$entry) return null;

        $change = $entry['changes'][0] ?? null;
        if (!$change) return null;

        $value = $change['value'] ?? null;
        if (!$value) return null;

        if (($change['field'] ?? '') !== 'comments') {
            return null;
        }

        return [
            'comment_id' => $value['id'] ?? null,
            'text'       => $value['text'] ?? '',
            'user_id'    => $value['from']['id'] ?? null,
            'username'   => $value['from']['username'] ?? null,
            'post_id'    => $value['media']['id'] ?? null,
        ];
    }

    /**
     * ─────────────────────────────────────────────
     * پردازش اصلی کامنت
     * ─────────────────────────────────────────────
     * این متد ۱۰ مرحله داره (مرحله ۱۱ حالا تو handle انجام می‌شه):
     *  ۱. پاک‌سازی متن
     *  ۲. پیدا/ساخت کاربر
     *  ۳. پیدا/ساخت پست
     *  ۴. چک اسپم
     *  ۵. اگه اسپم → ذخیره + return
     *  ۶. چک rate limit
     *  ۷. تحلیل کاربر
     *  ۸. پیدا/ساخت مکالمه
     *  ۹. ذخیره کامنت
     * ۱۰. اضافه کردن پیام به مکالمه
     */
    private function processComment(array $data): ?Comment
    {
        // ─── مرحله ۱: پاک‌سازی متن ───
        $sanitizedText = $this->sanitizer->sanitize($data['text']);

        if (empty($sanitizedText)) {
            return null;
        }

        // ─── مرحله ۲: پیدا یا ساخت کاربر ───
        $user = $this->findOrCreateUser($data['user_id'], $data['username']);

        // ─── مرحله ۳: پیدا یا ساخت پست ───
        $post = $this->findOrCreatePost($data['post_id']);

        // ─── مرحله ۴: چک اسپم ───
        $spamResult = $this->spamDetector->analyze($sanitizedText);

        // ─── مرحله ۵: اگه اسپم بود ───
        if ($spamResult['is_spam']) {
            $comment = Comment::create([
                'instagram_comment_id' => $data['comment_id'],
                'instagram_post_id'    => $post->id,
                'instagram_user_id'    => $user->id,
                'text'                 => $data['text'],
                'sanitized_text'       => $sanitizedText,
                'is_spam'              => true,
                'spam_score'           => $spamResult['score'],
                'status'               => 'rejected',
                'rejection_reason'     => implode(',', $spamResult['reasons']),
            ]);

            Log::info('Spam comment rejected', [
                'comment_id' => $comment->id,
                'score'      => $spamResult['score'],
            ]);

            return $comment;
        }

        // ─── مرحله ۶: چک rate limit ───
        $rateResult = $this->rateLimiter->check($user);

        if (!$rateResult['allowed']) {
            Log::warning('Rate limit exceeded', [
                'user_id' => $user->id,
                'reason'  => $rateResult['reason'],
            ]);

            return null;
        }

        // ─── مرحله ۷: تحلیل کاربر ───
        $this->userAnalyzer->analyze($user);

        // ─── مرحله ۸: پیدا یا ساخت مکالمه ───
        $conversation = $this->conversationManager->findOrCreate($user, $post);

        // ─── مرحله ۹: ذخیره کامنت ───
        $comment = Comment::create([
            'instagram_comment_id' => $data['comment_id'],
            'instagram_post_id'    => $post->id,
            'instagram_user_id'    => $user->id,
            'conversation_id'      => $conversation->id,
            'text'                 => $data['text'],
            'sanitized_text'       => $sanitizedText,
            'is_spam'              => false,
            'spam_score'           => $spamResult['score'],
            'status'               => 'pending',
        ]);

        // ─── مرحله ۱۰: اضافه کردن پیام کاربر به مکالمه ───
        $this->conversationManager->addMessage(
            $conversation,
            'user',
            $sanitizedText,
            ['instagram_comment_id' => $data['comment_id']]
        );

        Log::info('Comment processed successfully', [
            'comment_id'      => $comment->id,
            'conversation_id' => $conversation->id,
        ]);

        // ⚠️ مرحله ۱۱ حذف شد — الان تو متد handle انجام می‌شه
        return $comment;
    }

    /**
     * ─────────────────────────────────────────────
     * پیدا یا ساخت کاربر اینستاگرام
     * ─────────────────────────────────────────────
     */
    private function findOrCreateUser(?string $instagramId, ?string $username): InstagramUser
    {
        if (!$instagramId) {
            throw new \Exception('User id is required');
        }

        return InstagramUser::firstOrCreate(
            ['instagram_id' => $instagramId],
            [
                'username'     => $username,
                'last_seen_at' => now(),
            ]
        );
    }

    /**
     * ─────────────────────────────────────────────
     * پیدا یا ساخت پست اینستاگرام
     * ─────────────────────────────────────────────
     */
    private function findOrCreatePost(?string $instagramPostId): InstagramPost
    {
        if (!$instagramPostId) {
            throw new \Exception('Post id is required');
        }

        return InstagramPost::firstOrCreate(
            ['instagram_post_id' => $instagramPostId],
            [
                'posted_at' => now(),
            ]
        );
    }
}