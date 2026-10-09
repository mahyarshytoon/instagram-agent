<?php
// app/Services/Meta/FakeMetaAdapter.php

namespace App\Services\Meta;

use App\Exceptions\InstagramApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FakeMetaAdapter implements MetaAdapter
{
    public function __construct(
        private readonly string $apiBase,
        private readonly string $apiVersion = 'v21.0',
    ) {}

    public function replyToComment(string $commentId, string $message): array
    {
        $url = "{$this->apiBase}/{$this->apiVersion}/{$commentId}/replies";

        try {
            $response = Http::timeout(10)->post($url, [
                'message' => $message,
            ]);

            if (!$response->successful()) {
                throw new InstagramApiException(
                    "FakeMeta reply failed: HTTP {$response->status()}",
                    $response->status(),
                    $response->json()
                );
            }

            Log::info('FakeMeta reply sent', [
                'comment_id' => $commentId,
                'response'   => $response->json(),
            ]);

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('FakeMeta reply exception', [
                'comment_id' => $commentId,
                'error'      => $e->getMessage(),
            ]);
            
            throw $e instanceof InstagramApiException
                ? $e
                : new InstagramApiException($e->getMessage(), 0, [], $e);
        }
    }

    public function getUserInfo(string $userId): array
    {
        // FakeMeta — دیتای جعلی
        return [
            'id'       => $userId,
            'username' => 'fake_user_' . Str::random(6),
        ];
    }

    public function name(): string
    {
        return 'fake';
    }
}