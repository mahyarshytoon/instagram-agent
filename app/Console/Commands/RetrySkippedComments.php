<?php
// app/Console/Commands/RetrySkippedComments.php

namespace App\Console\Commands;

use App\Jobs\ValidateCommentJob;
use App\Models\Comment;
use Illuminate\Console\Command;

class RetrySkippedComments extends Command
{
    /**
     * ─────────────────────────────────────────────
     * امضای Command
     * ─────────────────────────────────────────────
     * comments:retry-skipped
     *   --dry-run    → فقط نشون بده، retry نکن
     *   --post=      → فقط کامنت‌های یه پست خاص
     *   --limit=     → حداکثر تعداد (پیش‌فرض: 100)
     */
    protected $signature = 'comments:retry-skipped
                            {--dry-run : فقط نشون بده، retry نکن}
                            {--post= : فقط کامنت‌های این پست}
                            {--limit=100 : حداکثر تعداد کامنت}';

    protected $description = 'Retry کامنت‌های skipped که الان پستشون محصول فعال داره';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $postId = $this->option('post');
        $limit  = (int) $this->option('limit');

        $this->info('🔍 پیدا کردن کامنت‌های skipped...');
        $this->newLine();

        // ─── ۱. Query پایه ───
        $query = Comment::query()
            ->where('status', 'skipped')
            ->whereHas('post', function ($q) {
                $q->whereHas('products', function ($q2) {
                    $q2->where('is_active', true);
                });
            })
            ->with(['post', 'instagramUser'])
            ->latest();

        // ─── ۲. فیلتر پست ───
        if ($postId) {
            $query->whereHas('post', function ($q) use ($postId) {
                $q->where('instagram_post_id', $postId);
            });
            $this->line("📌 فیلتر: پست <info>{$postId}</info>");
        }

        // ─── ۳. Limit ───
        $query->limit($limit);

        $comments = $query->get();

        if ($comments->isEmpty()) {
            $this->warn('⚠️  هیچ کامنت skipped قابل retry پیدا نشد.');
            $this->line('   (شاید پست‌هاشون هنوز محصول فعال ندارن)');
            return self::SUCCESS;
        }

        // ─── ۴. نمایش ───
        $this->info("🎯 {$comments->count()} کامنت پیدا شد:");
        $this->newLine();

        $this->table(
            ['ID', 'متن', 'پست', 'کاربر'],
            $comments->map(fn ($c) => [
                $c->id,
                mb_substr($c->sanitized_text ?? $c->text, 0, 30) . '...',
                $c->post?->instagram_post_id ?? '—',
                $c->instagramUser?->username ?? '—',
            ])->toArray()
        );

        // ─── ۵. اگه dry-run بود، تموم ───
        if ($dryRun) {
            $this->newLine();
            $this->info('🔵 حالت --dry-run: هیچ کامنتی retry نشد.');
            $this->line('   برای اجرای واقعی، --dry-run رو حذف کن.');
            return self::SUCCESS;
        }

        // ─── ۶. تأیید کاربر ───
        if (! $this->confirm('آیا می‌خوای این کامنت‌ها retry بشن؟', true)) {
            $this->warn('❌ لغو شد.');
            return self::SUCCESS;
        }

        // ─── ۷. Retry ───
        $this->newLine();
        $this->info('🚀 در حال dispatch...');
        $bar = $this->output->createProgressBar($comments->count());
        $bar->start();

        $dispatched = 0;
        foreach ($comments as $comment) {
            $comment->update([
                'status'            => 'pending',
                'rejection_reason'  => null,
                'processed_at'      => null,
            ]);

            ValidateCommentJob::dispatch($comment->id)
                ->onQueue('ai-responses');

            $dispatched++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$dispatched} کامنت retry شد.");
        $this->line('   برای دیدن نتیجه:');
        $this->line('   • Worker باید روشن باشه (ترمینال ۳)');
        $this->line('   • نتیجه بعد از چند ثانیه تو پنل ادمین ظاهر می‌شه');

        return self::SUCCESS;
    }
}