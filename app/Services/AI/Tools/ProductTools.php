<?php

namespace App\Services\AI\Tools;

use App\Models\Product;

class ProductTools
{
    /**
     * تعریف ابزارها برای DeepSeek
     */
    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_product_info',
                    'description' => 'اطلاعات کامل یه محصول رو از دیتابیس می‌گیره (قیمت، موجودی، توضیحات)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_name' => [
                                'type' => 'string',
                                'description' => 'اسم محصول (یا بخشی از اسم)',
                            ],
                        ],
                        'required' => ['product_name'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_products',
                    'description' => 'لیست همه محصولات موجود رو برمی‌گردونه',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'description' => 'دسته‌بندی (اختیاری)',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'check_stock',
                    'description' => 'موجودی یه محصول رو چک می‌کنه',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_name' => [
                                'type' => 'string',
                                'description' => 'اسم محصول',
                            ],
                        ],
                        'required' => ['product_name'],
                    ],
                ],
            ],
        ];
    }

    /**
     * اجرای ابزار
     */
    public static function execute(string $name, array $args): array
    {
        return match ($name) {
            'get_product_info' => self::getProductInfo($args['product_name']),
            'list_products'    => self::listProducts($args['category'] ?? null),
            'check_stock'      => self::checkStock($args['product_name']),
            default            => ['error' => "Unknown tool: {$name}"],
        };
    }

    protected static function getProductInfo(string $name): array
    {
        $product = Product::where('name', 'like', "%{$name}%")
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return ['found' => false, 'message' => "محصولی با نام '{$name}' پیدا نشد"];
        }

        return [
            'found'       => true,
            'name'        => $product->name,
            'description' => $product->description,
            'price'       => $product->price,
            'old_price'   => $product->old_price,
            'stock'       => $product->stock,
            'available'   => $product->stock > 0,
            'category'    => $product->category,
            'metadata'    => $product->metadata,
        ];
    }

    protected static function listProducts(?string $category = null): array
    {
        $query = Product::where('is_active', true);

        if ($category) {
            $query->where('category', 'like', "%{$category}%");
        }

        return $query->get()->map(fn($p) => [
            'name'      => $p->name,
            'price'     => $p->price,
            'stock'     => $p->stock,
            'available' => $p->stock > 0,
        ])->all();
    }

    protected static function checkStock(string $name): array
    {
        $product = Product::where('name', 'like', "%{$name}%")
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return ['found' => false];
        }

        return [
            'found'     => true,
            'name'      => $product->name,
            'stock'     => $product->stock,
            'available' => $product->stock > 0,
        ];
    }
}