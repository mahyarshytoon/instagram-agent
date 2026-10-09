<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name'        => 'کرم ضد آفتاب SPF50',
                'description' => 'مناسب پوست خشک و حساس، ضد آب، بدون چربی',
                'price'       => 250000,
                'old_price'   => 320000,
                'stock'       => 15,
                'category'    => 'مراقبت پوست',
                'is_active'   => true,
                'metadata'    => [
                    'برند'   => 'سینره',
                    'حجم'    => '50ml',
                    'رنگ'    => 'سفید',
                ],
            ],
            [
                'name'        => 'کرم آبرسان',
                'description' => 'آبرسان قوی برای پوست خشک، حاوی هیالورونیک اسید',
                'price'       => 180000,
                'old_price'   => null,
                'stock'       => 8,
                'category'    => 'مراقبت پوست',
                'is_active'   => true,
                'metadata'    => [
                    'برند' => 'سینره',
                    'حجم'  => '40ml',
                ],
            ],
            [
                'name'        => 'سرم ویتامین C',
                'description' => 'روشن‌کننده و ضد لک، مناسب همه پوست‌ها',
                'price'       => 350000,
                'old_price'   => 420000,
                'stock'       => 5,
                'category'    => 'سرم',
                'is_active'   => true,
                'metadata'    => [
                    'برند' => 'اوردینری',
                    'حجم'  => '30ml',
                ],
            ],
            [
                'name'        => 'پاک‌کننده آرایش',
                'description' => 'پاک‌کننده ملایم آرایش، مناسب چشم',
                'price'       => 95000,
                'old_price'   => null,
                'stock'       => 0,  // ← غیرموجود!
                'category'    => 'پاک‌کننده',
                'is_active'   => true,
                'metadata'    => [
                    'برند' => 'بایو درما',
                    'حجم'  => '200ml',
                ],
            ],
            [
                'name'        => 'ماسک صورت',
                'description' => 'ماسک ورقه‌ای آبرسان، مناسب همه پوست‌ها',
                'price'       => 45000,
                'old_price'   => 60000,
                'stock'       => 30,
                'category'    => 'ماسک',
                'is_active'   => true,
                'metadata'    => [
                    'برند' => 'مای',
                    'تعداد' => '۵ عدد',
                ],
            ],
        ];

        foreach ($products as $data) {
            Product::create($data);
        }
    }
}