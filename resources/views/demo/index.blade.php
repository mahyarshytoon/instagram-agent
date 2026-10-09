<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دمو — Instagram AI Agent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        body { font-family: Vazirmatn, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

{{-- Top bar شبیه اینستاگرام --}}
<header class="bg-white border-b sticky top-0 z-10">
    <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
        <h1 class="text-2xl font-bold bg-gradient-to-l from-pink-500 to-purple-600 bg-clip-text text-transparent">
            InstagramShop
        </h1>
        <p class="text-sm text-gray-500">دمو — دستیار هوشمند</p>
    </div>
</header>

<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-gray-800 mb-1">🛍️ پست‌های فروشگاه</h2>
        <p class="text-sm text-gray-500">روی هر پست کلیک کن تا کامنت‌ها و AI رو ببینی</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($products as $product)
            <a href="{{ route('demo.post', $product->id) }}"
               class="bg-white rounded-2xl shadow-md hover:shadow-xl transition overflow-hidden block">

                {{-- عکس پست (placeholder) --}}
                <div class="aspect-square bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 flex items-center justify-center relative">
                    <div class="text-center">
                        <div class="text-7xl mb-2">🛍️</div>
                        <p class="text-sm text-gray-500 font-medium">{{ $product->name }}</p>
                    </div>
                    @if($product->stock == 0)
                        <div class="absolute top-4 right-4 bg-red-500 text-white text-xs px-3 py-1 rounded-full font-bold">
                            ناموجود
                        </div>
                    @endif
                </div>

                {{-- اطلاعات پست --}}
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-pink-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                            IS
                        </div>
                        <span class="font-bold text-sm text-gray-800">instagram_shop</span>
                    </div>

                    <h3 class="font-bold text-gray-800 mb-1">{{ $product->name }}</h3>

                    @if($product->description)
                        <p class="text-xs text-gray-500 line-clamp-2 mb-2">{{ $product->description }}</p>
                    @endif

                    <div class="flex items-center gap-2">
                        @if($product->old_price)
                            <span class="text-xs text-gray-400 line-through">
                                {{ number_format($product->old_price) }}
                            </span>
                        @endif
                        <span class="font-bold text-indigo-600">{{ number_format($product->price) }}</span>
                        <span class="text-xs text-gray-500">تومان</span>
                    </div>

                    <div class="mt-3 pt-3 border-t flex items-center gap-4 text-gray-500 text-sm">
                        <span>❤️ {{ rand(50, 500) }}</span>
                        <span>💬 {{ \App\Models\Comment::where('instagram_post_id', 'demo_post_product_' . $product->id)->count() }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

</div>

</body>
</html>