{{-- resources/views/admin/conversations/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'مکالمات')
@section('page-title', '🎯 مکالمات')

@section('content')

{{-- ═══════ بخش ۱: آمار ═══════ --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-indigo-500">
        <p class="text-sm text-gray-500 mb-1">کل مکالمات</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-green-500">
        <p class="text-sm text-gray-500 mb-1">فعال</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['active']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-gray-400">
        <p class="text-sm text-gray-500 mb-1">بسته‌شده</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['closed']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-purple-500">
        <p class="text-sm text-gray-500 mb-1">کل پیام‌ها</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_msgs']) }}</p>
    </div>

</div>

{{-- ═══════ بخش ۲: فیلتر ═══════ --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <form method="GET" action="{{ route('admin.conversations.index') }}" class="flex flex-wrap gap-3 items-end">

        {{-- جستجو --}}
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-gray-500 mb-1">جستجو</label>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="ID مکالمه یا username..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
        </div>

        {{-- Status --}}
        <div class="w-40">
            <label class="block text-xs font-bold text-gray-500 mb-1">وضعیت</label>
            <select name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">همه</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>فعال</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>بسته‌شده</option>
            </select>
        </div>

        {{-- دکمه‌ها --}}
        <div class="flex gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-gradient-to-l from-pink-500 to-purple-600 text-white text-sm font-bold rounded-lg hover:shadow-lg transition">
                فیلتر
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.conversations.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition">
                    پاک کردن
                </a>
            @endif
        </div>

    </form>
</div>

{{-- ═══════ بخش ۳: جدول ═══════ --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">

    @if($conversations->isEmpty())
        <div class="p-12 text-center">
            <div class="text-5xl mb-3">📭</div>
            <p class="text-gray-500">هیچ مکالمه‌ای پیدا نشد.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">#</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">کاربر</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">پست</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">پیام‌ها</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">وضعیت</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">آخرین پیام</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($conversations as $conversation)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-500">{{ $conversation->id }}</td>

                            <td class="px-4 py-3">
                                <div class="text-xs">
                                    <div class="font-bold text-gray-700">
                                        {{ $conversation->user?->username ?? '—' }}
                                    </div>
                                    @if($conversation->user?->instagram_user_id)
                                        <div class="font-mono text-gray-400 mt-0.5">
                                            {{ \Illuminate\Support\Str::limit($conversation->user->instagram_user_id, 15) }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $conversation->post?->instagram_post_id ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">
                                    💬 {{ $conversation->message_count }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                @php
                                    $statusMap = [
                                        'active' => ['label' => 'فعال',      'class' => 'bg-green-100 text-green-700'],
                                        'closed' => ['label' => 'بسته‌شده',  'class' => 'bg-gray-100 text-gray-700'],
                                    ];
                                    $s = $statusMap[$conversation->status] ?? ['label' => $conversation->status, 'class' => 'bg-gray-100 text-gray-700'];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-bold {{ $s['class'] }}">
                                    {{ $s['label'] }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                @if($conversation->last_message_at)
                                    {{ $conversation->last_message_at->diffForHumans() }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <a href="{{ route('admin.conversations.show', $conversation->id) }}"
                                   class="text-purple-600 hover:text-purple-800 font-medium text-xs">
                                    مشاهده →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="px-4 py-4 border-t border-gray-200">
            {{ $conversations->links() }}
        </div>
    @endif

</div>

@endsection