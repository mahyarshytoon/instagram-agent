<?php
// app/Services/Meta/MetaAdapter.php

namespace App\Services\Meta;

use App\Exceptions\InstagramApiException;

interface MetaAdapter
{
    /**
     * پاسخ به یه کامنت
     * 
     * @param string $commentId شناسه کامنت در Meta
     * @param string $message   متن پاسخ
     * @return array            پاسخ Meta (شامل id پاسخ)
     * 
     * @throws InstagramApiException
     */
    public function replyToComment(string $commentId, string $message): array;

    /**
     * گرفتن اطلاعات کاربر
     * 
     * @param string $userId
     * @return array
     * 
     * @throws InstagramApiException
     */
    public function getUserInfo(string $userId): array;

    /**
     * نام Adapter (برای دیباگ و لاگ)
     */
    public function name(): string;
}