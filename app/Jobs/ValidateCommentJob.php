<?php
// app/Jobs/ValidateCommentJob.php

namespace App\Jobs;

use App\Exceptions\InstagramApiException;
use App\Models\Comment;
use App\Models\Response;
use App\Services\AIService;
use App\Services\ConversationManager;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidateCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];
    public int $timeout = 60;

    public int $commentId;

    public function __construct(int $commentId)
    {
        $this->commentId = $commentId;
    }

    public function handle(
        InstagramService $instagramService,
        ConversationManager $conversationManager
    ): void {
        // AIService رو تازه از container بگیر (جلوگیری از instance قدیمی)
        $aiService = app(AIService::class);

        $startTime = microtime(true);

        // ۱. کامنت رو بگیر
        $comment = Comment::with(['conversation', 'instagramUser', 'post'])
            ->find($this->commentId);

        if (! $comment) {
            Log::warning('ValidateCommentJob: comment not found', [
                'comment_id' => $this->commentId,
            ]);
            return;
        }

        // ۲. Idempotency
        if (in_array($comment->status, ['processed', 'permanently_failed'])) {
            Log::info('ValidateCommentJob: already handled', [
                'comment_id' => $this->commentId,
                'status'     => $comment->status,
            ]);
            return;
        }

        // ۳. status → processing
        $comment->update(['status' => 'processing']);

        try {
            // ۴. محصولات پست رو چک کن
            $hasProducts = $comment->post?->products()
                ->where('is_active', true)
                ->exists() ?? false;

            if (! $hasProducts) {
                Log::warning('ValidateCommentJob: no product for post', [
                    'comment_id' => $this->commentId,
                    'post_id'    => $comment->instagram_post_id,
                ]);
                $comment->update(['status' => 'skipped']);
                return;
            }

            // ۵. تاریخچه مکالمه
            $conversationHistory = $conversationManager->getHistory(
                $comment->conversation_id
            );

            // ۶. AI رو صدا بزن
            $aiResult = $aiService->generateReply(
                comment: $comment,
                product: null,
                history: $conversationHistory
            );

            // ← لاگ برای دیباگ
            Log::info('ValidateCommentJob: aiResult received', [
                'success'       => $aiResult['success'] ?? false,
                'model'         => $aiResult['model'] ?? 'MISSING',
                'input_tokens'  => $aiResult['input_tokens'] ?? 'MISSING',
                'output_tokens' => $aiResult['output_tokens'] ?? 'MISSING',
            ]);

            if (! $aiResult['success']) {
                throw new \RuntimeException(
                    'AI failed: ' . ($aiResult['error'] ?? 'unknown')
                );
            }

            $replyText = $aiResult['text'];

            // ← استخراج مقادیر AI قبل از transaction (خارج از closure)
            $aiModel      = $aiResult['model']         ?? null;
            $inputTokens  = $aiResult['input_tokens']  ?? 0;
            $outputTokens = $aiResult['output_tokens'] ?? 0;

            // ۷. جواب رو تو اینستاگرام بفرست
            $sendResult = $instagramService->replyToComment(
                commentId: $comment->instagram_comment_id,
                message: $replyText
            );

            // ۸. ذخیره تو دیتابیس
            DB::transaction(function () use (
                $comment,
                $replyText,
                $sendResult,
                $aiModel,
                $inputTokens,
                $outputTokens
            ) {
                Response::create([
                    'comment_id'         => $comment->id,
                    'conversation_id'    => $comment->conversation_id,
                    'type'               => 'ai',
                    'content'            => $replyText,
                    'ai_model'           => $aiModel,
                    'input_tokens'       => $inputTokens,
                    'output_tokens'      => $outputTokens,
                    'instagram_reply_id' => $sendResult['reply_id'] ?? null,
                    'status'             => 'sent',
                    'sent_at'            => now(),
                ]);

                $comment->update([
                    'status'       => 'processed',
                    'processed_at' => now(),
                ]);

                $comment->conversation?->update([
                    'last_message_at' => now(),
                ]);
            });

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::info('ValidateCommentJob: success', [
                'comment_id'  => $this->commentId,
                'duration_ms' => $duration,
                'reply_len'   => mb_strlen($replyText),
            ]);
        } catch (InstagramApiException $e) {
            $this->handleInstagramError($comment, $e);
        } catch (\Throwable $e) {
            $comment->update(['status' => 'failed']);

            Log::error('ValidateCommentJob: failed', [
                'comment_id' => $this->commentId,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'attempt'    => $this->attempts(),
            ]);

            throw $e;
        }
    }

    protected function handleInstagramError(Comment $comment, InstagramApiException $e): void
    {
        if ($e->isAuthError()) {
            Log::critical('ValidateCommentJob: Instagram auth error', [
                'comment_id' => $this->commentId,
                'endpoint'   => $e->getEndpoint(),
                'message'    => $e->getReadableMessage(),
            ]);
            $comment->update(['status' => 'failed_auth']);
            return;
        }

        if ($e->isPermanent()) {
            Log::warning('ValidateCommentJob: Instagram permanent error', [
                'comment_id' => $this->commentId,
                'endpoint'   => $e->getEndpoint(),
                'message'    => $e->getReadableMessage(),
            ]);
            $comment->update(['status' => 'skipped']);
            return;
        }

        if ($e->isRetryable()) {
            Log::warning('ValidateCommentJob: Instagram retryable error', [
                'comment_id' => $this->commentId,
                'endpoint'   => $e->getEndpoint(),
                'message'    => $e->getReadableMessage(),
                'attempt'    => $this->attempts(),
            ]);
            $comment->update(['status' => 'failed']);
            throw $e;
        }

        Log::error('ValidateCommentJob: unknown Instagram error', [
            'comment_id' => $this->commentId,
            'status'     => $e->getStatusCode(),
            'message'    => $e->getReadableMessage(),
        ]);
        $comment->update(['status' => 'failed']);
        throw $e;
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('ValidateCommentJob: permanently failed', [
            'comment_id' => $this->commentId,
            'error'      => $e->getMessage(),
        ]);

        Comment::where('id', $this->commentId)
            ->update(['status' => 'permanently_failed']);
    }
}