<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetProductInfoTool implements Tool
{
    /**
     * نام ابزار (برای AI)
     */
    public function name(): string
    {
        return 'get_product_info';
    }

    /**
     * توضیح ابزار (AI از این می‌فهمه کِی استفاده کنه)
     */
    public function description(): string
    {
        return 'اطلاعات کامل یه محصول رو از دیتابیس می‌گیره (قیمت، موجودی، توضیحات). وقتی کاربر درباره قیمت یا موجودی یه محصول پرسید از این استفاده کن.';
    }

    /**
     * اسکیمای ورودی (AI باید چی بفرسته)
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product_name' => $schema->string()
                ->description('اسم محصول یا بخشی از اسمش')
                ->required(),
        ];
    }

    /**
     * منطق اجرا (وقتی AI این ابزار رو صدا زد)
     */
    public function handle(Request $request): string
    {
        $name = $request->string('product_name');

        // جستجو تو دیتابیس (LIKE برای انعطاف)
        $product = Product::where('name', 'like', "%{$name}%")
            ->where('is_active', true)
            ->first();

        // اگه پیدا نشد
        if (!$product) {
            return "محصولی با نام «{$name}» پیدا نشد.";
        }

        // فرمت خروجی برای AI
        $output = "اطلاعات محصول:\n";
        $output .= "- نام: {$product->name}\n";
        $output .= "- قیمت: " . number_format($product->price) . " تومان\n";

        if ($product->old_price) {
            $output .= "- قیمت قبلی: " . number_format($product->old_price) . " تومان\n";
        }

        $output .= "- موجودی: " . ($product->stock > 0 ? "{$product->stock} عدد" : "ناموجود") . "\n";

        if ($product->description) {
            $output .= "- توضیحات: {$product->description}\n";
        }

        return $output;
    }
}