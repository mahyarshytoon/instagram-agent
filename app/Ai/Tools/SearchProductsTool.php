<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchProductsTool implements Tool
{
    public function name(): string
    {
        return 'search_products';
    }

    public function description(): string
    {
        return 'جستجوی محصولات تو دیتابیس بر اساس نام، دسته‌بندی یا کلمه کلیدی. '
            . 'وقتی کاربر درباره محصولی غیر از محصول پست فعلی می‌پرسه، از این ابزار استفاده کن. '
            . 'مثلاً «شامپو دارید؟»، «محصولات ضد چین و چروک»، «چی برای مو دارید».';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('کلمه کلیدی جستجو، مثلاً «شامپو» یا «ضد آفتاب»')
                ->required(),
            'category' => $schema->string()
                ->description('دسته‌بندی اختیاری، مثلاً «مراقبت مو» یا «مراقبت پوست»'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = trim((string) $request->string('query'));
        $category = trim((string) $request->string('category'));

        $builder = Product::where('is_active', true);

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%");
            });
        }

        if ($category !== '') {
            $builder->where('category', 'like', "%{$category}%");
        }

        $products = $builder->limit(5)->get();

        if ($products->isEmpty()) {
            return "محصولی با این مشخصات پیدا نشد. به کاربر بگو که چنین محصولی موجود نیست و اگه بخواد می‌تونه سوال دیگه‌ای بپرسه.";
        }

        $output = "محصولات پیدا شده:\n\n";
        foreach ($products as $p) {
            $stockText = $p->stock > 0 ? "{$p->stock} عدد موجود" : "ناموجود";
            $output .= "• {$p->name}\n";
            $output .= "  قیمت: " . number_format((float) $p->price) . " تومان\n";
            if ($p->old_price) {
                $output .= "  قیمت قبلی: " . number_format((float) $p->old_price) . " تومان\n";
            }
            $output .= "  موجودی: {$stockText}\n";
            if ($p->description) {
                $output .= "  توضیحات: " . mb_substr($p->description, 0, 150) . "\n";
            }
            $output .= "\n";
        }

        return $output;
    }
}