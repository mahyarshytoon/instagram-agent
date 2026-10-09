<?php
// app/Http/Controllers/Fake/FakeMetaController.php

namespace App\Http\Controllers\Fake;

use App\Http\Controllers\Controller;
use App\Models\FakeMetaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FakeMetaController extends Controller
{
    /**
     * POST /api/fake-meta/{version}/{commentId}/replies
     * پاسخ به کامنت — همون endpoint که InstagramService صداش می‌زنه
     */
    public function replyToComment(
        Request $request,
        string $version,
        string $commentId
    ): JsonResponse {
        $response = [
            'id' => 'fake_reply_' . Str::random(12),
        ];

        $this->log($request, "POST /{$version}/{$commentId}/replies", $response);

        return response()->json($response, 200);
    }

    /**
     * GET /api/fake-meta/_logs
     * برای دیباگ پنل ادمین
     */
    public function logs(): JsonResponse
    {
        return response()->json(
            FakeMetaRequest::latest()->limit(50)->get()
        );
    }

    private function log(Request $request, string $endpoint, array $response): void
    {
        FakeMetaRequest::create([
            'method'   => $request->method(),
            'endpoint' => $endpoint,
            'headers'  => $this->sanitizeHeaders($request->headers->all()),
            'payload'  => $request->all(),
            'response' => $response,
        ]);
    }

    /**
     * پاک کردن توکن از هدرها (امنیت)
     */
    private function sanitizeHeaders(array $headers): array
    {
        foreach (['authorization', 'x-hub-signature-256'] as $key) {
            if (isset($headers[$key])) {
                $headers[$key] = ['***REDACTED***'];
            }
        }
        return $headers;
    }
}