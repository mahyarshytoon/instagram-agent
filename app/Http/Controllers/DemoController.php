<?php

namespace App\Http\Controllers;

use App\Jobs\ValidateCommentJob;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\InstagramPost;
use App\Models\InstagramUser;
use App\Models\Product;
use App\Services\ConversationManager;
use App\Services\SpamDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DemoController extends Controller
{
    public function __construct(
        private ConversationManager $conversationManager,
        private SpamDetector $spamDetector,
    ) {
    }

    public function index()
    {
        $products = Product::where('is_active', true)->get();

        return view('demo.index', compact('products'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'text'       => 'required|string|min:2|max:500',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $startTime = microtime(true);

        try {
            $sessionKey = substr(md5(session()->getId()), 0, 12);

            $user = InstagramUser::firstOrCreate(
                ['instagram_id' => "demo_user_{$sessionKey}"],
                ['username' => "demo_visitor_{$sessionKey}"]
            );

            $post = InstagramPost::firstOrCreate(
                ['instagram_post_id' => "demo_post_product_{$product->id}"],
                ['posted_at' => now()]
            );

            $post->products()->syncWithoutDetaching([$product->id]);

            $conversation = Conversation::firstOrCreate(
                [
                    'instagram_user_id' => $user->id,
                    'instagram_post_id' => $post->id,
                ],
                [
                    'status' => 'active',
                    'started_at' => now(),
                    'last_message_at' => now(),
                ]
            );

            // 🆕 چک اسپم قبل از ذخیره
            $spamResult = $this->spamDetector->analyze($validated['text']);

            $comment = Comment::create([
                'instagram_comment_id' => 'demo_cmt_' . Str::random(12),
                'instagram_post_id'    => $post->id,
                'instagram_user_id'    => $user->id,
                'conversation_id'      => $conversation->id,
                'text'                 => $validated['text'],
                'sanitized_text'       => $validated['text'],
                'is_spam'              => $spamResult['is_spam'],
                'spam_score'           => $spamResult['score'],
                'status'               => $spamResult['is_spam'] ? 'rejected' : 'pending',
                'rejection_reason'     => $spamResult['is_spam']
                    ? implode(',', $spamResult['reasons'])
                    : null,
            ]);

            // 🆕 اگه اسپم بود → AI صدا زده نشه
            if ($spamResult['is_spam']) {
                Log::info('Demo: comment rejected as spam', [
                    'comment_id' => $comment->id,
                    'score'      => $spamResult['score'],
                    'reasons'    => $spamResult['reasons'],
                ]);

                return response()->json([
                    'success' => true,
                    'spam'    => true,
                    'comment' => [
                        'text'   => $comment->text,
                        'status' => 'rejected',
                    ],
                    'reply'   => '🚫 این کامنت به دلیل محتوای نامناسب پاک شد. لطفاً سوال محترمانه‌ای بپرسید.',
                    'reason'  => $comment->rejection_reason,
                ]);
            }

            // اضافه کردن به مکالمه (فقط اگه اسپم نبود)
            $this->conversationManager->addMessage(
                $conversation,
                'user',
                $validated['text'],
                ['instagram_comment_id' => $comment->instagram_comment_id]
            );

            // AI رو صدا بزن
            ValidateCommentJob::dispatchSync($comment->id);

            $comment->refresh();
            $response = $comment->responses()->latest()->first();

            $duration = round(microtime(true) - $startTime, 2);

            return response()->json([
                'success' => true,
                'comment' => [
                    'text'   => $comment->text,
                    'status' => $comment->status,
                ],
                'reply'   => $response?->content ?? 'پاسخی تولید نشد',
                'meta'    => [
                    'duration_sec' => $duration,
                    'tokens'       => 0,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Demo comment failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
	
	public function show(Product $product)
{
    // پست مربوط به این محصول
    $post = InstagramPost::firstOrCreate(
        ['instagram_post_id' => "demo_post_product_{$product->id}"],
        ['posted_at' => now()]
    );

    $post->products()->syncWithoutDetaching([$product->id]);

    // کامنت‌های این پست (آخرین ۵۰ تا)
    $comments = Comment::where('instagram_post_id', $post->id)
        ->with(['responses', 'instagramUser'])
        ->orderBy('created_at', 'asc')
        ->limit(50)
        ->get();

    return view('demo.post', compact('product', 'post', 'comments'));
}
}