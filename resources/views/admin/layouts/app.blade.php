{{-- resources/views/admin/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'داشبورد') — {{ config('admin.ui.title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        body { font-family: Vazirmatn, sans-serif; }
        .sidebar-link.active { background: linear-gradient(to left, #ec4899, #9333ea); color: white; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="flex min-h-screen">

    {{-- ═══════ SIDEBAR (فهرست کنار) ═══════ --}}
    <aside class="w-64 bg-white border-l border-gray-200 flex-shrink-0 hidden md:flex flex-col">
        
        {{-- Logo --}}
        <div class="px-6 py-5 border-b border-gray-200">
            <h1 class="text-lg font-bold bg-gradient-to-l from-pink-500 to-purple-600 bg-clip-text text-transparent">
                🎛️ پنل مدیریت
            </h1>
            <p class="text-xs text-gray-400 mt-1">Instagram Agent</p>
        </div>

        {{-- Menu --}}
        <nav class="flex-1 p-4 space-y-1">

            @php
               
			$menu = [
				['route' => 'admin.dashboard',       'label' => 'داشبورد',       'icon' => '📊'],
				['route' => 'admin.comments.index',  'label' => 'کامنت‌ها',       'icon' => '💬'],
				['route' => 'admin.conversations.index',   'label' => 'مکالمات',       'icon' => '🎯'],
				['route' => 'admin.settings.index',  'label' => 'تنظیمات',       'icon' => '⚙️'],
				['route' => 'admin.meta-logs.index', 'label' => 'لاگ‌های Meta',   'icon' => '📝'],
			];
            @endphp

            @foreach($menu as $item)
                @php
                    $isActive = request()->routeIs($item['route']) 
                        || request()->routeIs(str_replace('.index', '.*', $item['route']));
                @endphp
                <a href="{{ route($item['route']) }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition {{ $isActive ? 'active' : '' }}">
                    <span class="text-lg">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Footer --}}
        <div class="p-4 border-t border-gray-200">
            <p class="text-xs text-gray-400 text-center">v1.0 — {{ date('Y') }}</p>
        </div>
    </aside>

    {{-- ═══════ MAIN (محتوای اصلی) ═══════ --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Header --}}
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800">@yield('page-title', 'داشبورد')</h2>
            
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">👤 ادمین</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">
                        خروج
                    </button>
                </form>
            </div>
        </header>

        {{-- Content (محتوا) --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t border-gray-200 px-6 py-3 text-center">
            <p class="text-xs text-gray-400">{{ config('admin.ui.title') }}</p>
        </footer>
    </div>

</div>

</body>
</html>