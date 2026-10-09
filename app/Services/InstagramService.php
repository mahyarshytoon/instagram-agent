<?php
// app/Services/InstagramService.php

namespace App\Services;

use App\Exceptions\InstagramApiException;
use App\Services\Meta\MetaAdapter;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    /**
     * MetaAdapter تزریق شده از Service Container
     * 
     * این Adapter می‌تونه:
     *  - FakeMetaAdapter (dev)   → http://127.0.0.1:8001
     *  - RealMetaAdapter (prod)  → https://graph.facebook.com
     * 
     * تصمیم تو config/admin.php: 'meta.adapter'
     * یعنی فردا فقط .env عوض کن، این کد تغییر نمی‌کنه.
     */
    public function __construct(
        private readonly MetaAdapter $meta,
    ) {}

    /**
     * پاسخ دادن به یک کامنت
     * 
     * @param string $commentId شناسه کامنت
     * @param string $message   متن پاسخ
     * @return array            ['success' => true, 'reply_id' => '...', 'error' => null]
     * 
     * @throws InstagramApiException
     */
    public function replyToComment(string $commentId, string $message): array
    {
        try {
            $result = $this->meta->replyToComment($commentId, $message);

            return [
                'success'  => true,
                'reply_id' => $result['id'] ?? null,
                'error'    => null,
            ];
        } catch (InstagramApiException $e) {
            Log::warning('InstagramService::replyToComment failed', [
                'comment_id' => $commentId,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success'  => false,
                'reply_id' => null,
                'error'    => $e->getMessage(),
            ];
        }
    }

    /**
     * دریافت اطلاعات یک پست
     * 
     * @throws InstagramApiException
     */
    public function getPost(string $postId): array
    {
        // ⚠️ نکته: MetaAdapter فعلی این متد رو نداره
        // اگه لازم داری، به interface اضافه کن
        // فعلاً از خود MetaAdapter استفاده نمی‌کنیم چون در interface نیست
        
        // TODO: به MetaAdapter اضافه کن اگه لازم شد
        return [];
    }

    /**
     * دریافت اطلاعات یک کاربر
     */
    public function getUser(string $userId): array
    {
        return $this->meta->getUserInfo($userId);
    }

    /**
     * نام Adapter فعال (برای دیباگ)
     */
    public function adapterName(): string
    {
        return $this->meta->name();
    }
}