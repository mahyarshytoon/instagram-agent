{{-- resources/views/admin/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود — {{ config('admin.ui.title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        body { font-family: Vazirmatn, sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">

    {{-- کارت لاگین --}}
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-l from-pink-500 to-purple-600 px-6 py-8 text-center">
            <div class="text-5xl mb-3">🔐</div>
            <h1 class="text-2xl font-bold text-white mb-1">پنل مدیریت</h1>
            <p class="text-sm text-white/80">{{ config('admin.ui.title') }}</p>
        </div>

        {{-- Body --}}
        <div class="p-8">

            {{-- پیام خطا از session --}}
            @if(session('auth_error'))
                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg text-sm">
                    @switch(session('auth_error'))
                        @case('session_expired')
                            ⏰ نشست شما منقضی شده. لطفاً دوباره وارد شوید.
                            @break
                        @case('no_session')
                            🔒 لطفاً برای دسترسی وارد شوید.
                            @break
                        @default
                            ⚠️ برای دسترسی، لطفاً وارد شوید.
                    @endswitch
                </div>
            @endif

            {{-- فرم لاگین --}}
            <form method="POST" action="{{ url(config('admin.route.prefix') . '/login') }}">
                @csrf

                {{-- فیلد رمز --}}
                <div class="mb-6">
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-2">
                        رمز عبور
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('password') border-red-500 @enderror"
                        placeholder="••••••••"
                    >

                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- دکمه --}}
                <button
                    type="submit"
                    class="w-full bg-gradient-to-l from-pink-500 to-purple-600 text-white font-bold py-3 rounded-lg hover:shadow-lg transition"
                >
                    ورود به پنل
                </button>
            </form>

            {{-- لینک بازگشت --}}
            <div class="mt-6 text-center">
                <a href="{{ url('/demo') }}" class="text-sm text-gray-500 hover:text-purple-600">
                    ← بازگشت به دمو
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 bg-gray-50 border-t text-center">
            <p class="text-xs text-gray-400">
                {{ config('admin.ui.title') }} — v1.0
            </p>
        </div>
    </div>

    {{-- IP نمایش (برای دیباگ) --}}
    @if(config('app.debug'))
        <div class="mt-4 text-center text-xs text-gray-400">
            IP شما: {{ request()->ip() }}
        </div>
    @endif
</div>

</body>
</html>