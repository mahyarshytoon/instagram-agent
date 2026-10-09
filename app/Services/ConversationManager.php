<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\InstagramPost;
use App\Models\InstagramUser;
use App\Models\Message;
use Carbon\Carbon;

class ConversationManager
{
    public function findOrCreate(InstagramUser $user, InstagramPost $post): Conversation
    {
        $windowMinutes = config('agent.conversation.merge_window_minutes', 30);
        $cutoffTime = Carbon::now()->subMinutes($windowMinutes);

        $conversation = Conversation::where('instagram_user_id', $user->id)
            ->where('instagram_post_id', $post->id)
            ->where('status', 'active')
            ->where(function ($q) use ($cutoffTime) {
                $q->whereNull('last_message_at')
                  ->orWhere('last_message_at', '>=', $cutoffTime);
            })
            ->latest('last_message_at')
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return Conversation::create([
            'instagram_user_id' => $user->id,
            'instagram_post_id' => $post->id,
            'status'            => 'active',
            'message_count'     => 0,
            'started_at'        => now(),
            'last_message_at'   => now(),
        ]);
    }

    public function addMessage(
        Conversation $conversation,
        string $role,
        string $content,
        array $extra = []
    ): Message {
        $message = $conversation->messages()->create(array_merge([
            'role'    => $role,
            'content' => $content,
        ], $extra));

        $conversation->increment('message_count');
        $conversation->update(['last_message_at' => now()]);

        $maxMessages = config('agent.conversation.max_messages', 20);
        if ($conversation->message_count >= $maxMessages) {
            $conversation->update([
                'status'    => 'closed',
                'closed_at' => now(),
            ]);
        }

        return $message;
    }

    public function close(Conversation $conversation): void
    {
        $conversation->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function archiveOldConversations(int $days = 7): int
    {
        return Conversation::where('status', 'closed')
            ->where('closed_at', '<', now()->subDays($days))
            ->update(['status' => 'archived']);
    }
	
	
	/**
 * گرفتن تاریخچه مکالمه برای AI
 *
 * این متد پیام‌های مکالمه رو به فرمت OpenAI برمی‌گردونه:
 * [
 *     ['role' => 'user', 'content' => 'سلام'],
 *     ['role' => 'assistant', 'content' => 'سلام! چطور می‌تونم کمک کنم؟'],
 *     ...
 * ]
 *
 * @param int|null $conversationId
 * @param int $limit حداکثر تعداد پیام (برای کنترل token)
 * @return array
 */
	public function getHistory(?int $conversationId, int $limit = 10): array
	{
		if (!$conversationId) {
			return [];
		}

		$conversation = Conversation::find($conversationId);

		if (!$conversation) {
			return [];
		}

		// آخرین N پیام رو بگیر (به ترتیب صعودی)
		$messages = $conversation->messages()
			->orderBy('created_at', 'desc')
			->limit($limit)
			->get()
			->reverse()
			->values();

		return $messages->map(function ($message) {
			return [
				'role'    => $message->role,     // 'user' یا 'assistant'
				'content' => $message->content,
			];
		})->toArray();
	}
}