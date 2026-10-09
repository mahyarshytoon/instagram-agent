<?php
// app/Services/Meta/RealMetaAdapter.php

namespace App\Services\Meta;

use App\Exceptions\InstagramApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealMetaAdapter implements MetaAdapter
{
    public function __construct(
        private readonly string $apiBase,
        private readonly string $accessToken,
        private readonly string $apiVersion = 'v21.0',
    ) {}

    public function replyToComment(string $commentId, string $message): array
    {
        $url = "{$this->apiBase}/{$this->apiVersion}/{$commentId}/replies";

        try {
            $response = Http::timeout(15)
                ->withToken($this->accessToken)
                ->post($url, ['message' => $message]);

            if (!$response->successful()) {
                $error = $response->json('error', []);
                
                // خطاهای Meta رو با دقت هندل کن
                $isRetryable = in_array($response->status(), [429, 500, 502, 503, 504]);
                $isAuthError = $response->status() === 401 
                    || ($error['code'] ?? null) === 190; // 190 = invalid token

                throw new InstagramApiException(
                    $error['message'] ?? "Meta API error: HTTP {$response->status()}",
                    $response->status(),
                    $error,
                    null,
                    $isRetryable,
                    $isAuthError
                );
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Meta reply exception', [
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
        $url = "{$this->apiBase}/{$this->apiVersion}/{$userId}";

        $response = Http::timeout(10)
            ->withToken($this->accessToken)
            ->get($url, ['fields' => 'id,username,name,profile_picture_url']);

        if (!$response->successful()) {
            throw new InstagramApiException(
                "Meta getUserInfo failed",
                $response->status(),
                $response->json()
            );
        }

        return $response->json();
    }

    public function name(): string
    {
        return 'real';
    }
}