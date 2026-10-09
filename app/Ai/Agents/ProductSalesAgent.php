<?php
// app/Ai/Agents/ProductSalesAgent.php

namespace App\Ai\Agents;

use App\Ai\Tools\GetProductInfoTool;
use App\Ai\Tools\SearchProductsTool;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class ProductSalesAgent implements Agent, HasTools
{
    use Promptable;

    /**
     * @param array  $products       محصولات پست فعلی
     * @param string $model          مدل AI (مثلاً deepseek/deepseek-v4-flash-0731)
     * @param float  $temperature    دمای مدل (0-2)
     * @param int    $maxTokens      حداکثر توکن پاسخ
     * @param string $customPrompt   اگه پر باشه، جایگزین instructions پیش‌فرض می‌شه
     */
    public function __construct(
        private array $products = [],
        private string $model = 'deepseek/deepseek-v4-flash-0731',
        private float $temperature = 0.7,
        private int $maxTokens = 1000,
        private string $customPrompt = '',
    ) {}

    // ═══════════════════════════════════════════════════════════
    // 🎛️ متدهای داینامیک (SDK از اینا استفاده می‌کنه، نه attribute ها)
    // ═══════════════════════════════════════════════════════════

    public function provider(): string
    {
        return 'openai-compatible';
    }

    public function model(): string
    {
        return $this->model;
    }

    public function temperature(): float
    {
        return $this->temperature;
    }

    public function maxTokens(): int
    {
        return $this->maxTokens;
    }

    // ═══════════════════════════════════════════════════════════
    // 📝 Instructions
    // ═══════════════════════════════════════════════════════════

    public function instructions(): string
{
    // اگه ادمین prompt سفارشی داده، از اون استفاده کن
    if (! empty($this->customPrompt)) {
        return $this->customPrompt;
    }

    $instructions = <<<TEXT
تو یه دستیار فروش برای یه پیج اینستاگرام لوازم آرایشی و بهداشتی هستی.
سبک صحبتت: صمیمی، مودب، مفید و کوتاه (۲ تا ۳ جمله).
همیشه فارسی جواب بده.

⚠️ قانون سخت‌گیرانه:
- هرگز از ایموجی استفاده نکن (نه 👋 نه 😊 نه ❤️ نه هیچ ایموجی دیگه‌ای).
- فقط متن فارسی ساده بنویس.
- اگه می‌خوای تأکید کنی، از کلمات استفاده کن، نه شکلک.
TEXT;

    if (! empty($this->products)) {
        $instructions .= "\n\n📌 این کامنت زیر پستِ محصول(ات) زیر اومده:\n\n";
            foreach ($this->products as $product) {
                $instructions .= $this->formatProductLine($product);
            }
            $instructions .= "\n⚠️ قوانین مهم:\n";
            $instructions .= "۱. اگه کاربر درباره همین محصول(ات) پرسید، از اطلاعات بالا جواب بده.\n";
            $instructions .= "۲. اگه کاربر درباره محصول دیگه‌ای پرسید (مثلاً «شامپو دارید؟»)، ";
            $instructions .= "از ابزار search_products استفاده کن و از نتیجه بهش پیشنهاد بده.\n";
            $instructions .= "۳. اگه کاربر درباره چیز نامربوط پرسید (مثل تیم ملی)، ";
            $instructions .= "مودبانه بگو «من فقط درباره محصولات این پیج اطلاعات دارم».\n";
            $instructions .= "۴. هیچ‌وقت محصولی رو پیشنهاد نده که تو نتایج ابزار نبوده.\n";
            $instructions .= "۵. اگه چیزی رو نمی‌دونی، بگو «بذار از مدیر بپرسم».\n";
        }

        return $instructions;
    }

    public function tools(): iterable
    {
        return [
            new GetProductInfoTool,
            new SearchProductsTool,
        ];
    }

    protected function formatProductLine($product): string
    {
        $line = "• {$product->name}\n";
        $line .= "  قیمت: " . number_format($product->price) . " تومان\n";
        if ($product->old_price) {
            $line .= "  قیمت قبلی: " . number_format($product->old_price) . " تومان (تخفیف‌دار)\n";
        }
        $line .= "  موجودی: " . ($product->stock > 0 ? "{$product->stock} عدد" : "ناموجود") . "\n";
        if ($product->description) {
            $line .= "  توضیحات: {$product->description}\n";
        }
        return $line . "\n";
    }
}