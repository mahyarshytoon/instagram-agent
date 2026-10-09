<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InstagramApiException extends Exception
{
    /**
     * کد وضعیت HTTP که از اینستاگرام برگشته
     * مثال: 401, 429, 500
     */
    protected int $statusCode;

    /**
     * بدنه پاسخ اینستاگرام (برای debug)
     * مثال: ['error' => ['message' => 'Invalid token']]
     */
    protected ?array $responseBody;

    /**
     * آدرسی که درخواست بهش فرستاده شده
     * مثال: https://graph.instagram.com/v21.0/123/replies
     */
    protected string $endpoint;

    /**
     * سازنده
     *
     * @param string $message پیام خطا (برای نمایش)
     * @param int $statusCode کد HTTP
     * @param array|null $responseBody بدنه پاسخ
     * @param string $endpoint آدرس درخواست
     * @param Throwable|null $previous خطای قبلی (برای زنجیره)
     */
    public function __construct(
        string $message = '',
        int $statusCode = 0,
        ?array $responseBody = null,
        string $endpoint = '',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->endpoint = $endpoint;
    }

    /**
     * گرفتن کد وضعیت HTTP
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * گرفتن بدنه پاسخ
     */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    /**
     * گرفتن endpoint درخواست
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * آیا این خطا موقتی است و باید retry کنیم؟
     *
     * 429 = Rate Limit (محدودیت نرخ)
     * 500, 502, 503, 504 = خطاهای سرور اینستاگرام
     */
    public function isRetryable(): bool
    {
        return in_array($this->statusCode, [
		    0,    // ✅ خطای شبکه (cURL error 7, 28, ...)
            429,  // Too Many Requests
            500,  // Internal Server Error
            502,  // Bad Gateway
            503,  // Service Unavailable
            504,  // Gateway Timeout
        ], true);
    }

    /**
     * آیا مشکل از توکن/احراز هویت است؟
     *
     * 401 = Unauthorized (توکن نامعتبر)
     * 403 = Forbidden (دسترسی نداریم)
     */
    public function isAuthError(): bool
    {
        return in_array($this->statusCode, [401, 403], true);
    }

    /**
     * آیا خطا دائمی است؟ (retry فایده نداره)
     *
     * 400 = Bad Request (درخواست اشتباه)
     * 404 = Not Found (کامنت حذف شده)
     */
    public function isPermanent(): bool
    {
        return in_array($this->statusCode, [400, 404], true);
    }

    /**
     * پیام خطای خوانا برای لاگ
     */
    public function getReadableMessage(): string
    {
        $errorMessage = $this->responseBody['error']['message']
            ?? $this->getMessage()
            ?? 'Unknown Instagram API error';

        return sprintf(
            '[%d] %s (endpoint: %s)',
            $this->statusCode,
            $errorMessage,
            $this->endpoint
        );
    }
}