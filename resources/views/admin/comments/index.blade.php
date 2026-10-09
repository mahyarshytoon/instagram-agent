{{-- resources/views/admin/comments/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'کامنت‌ها')
@section('page-title', '💬 کامنت‌ها')

@section('content')

{{-- ═══════ بخش ۱: آمار ═══════ --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-indigo-500">
        <p class="text-sm text-gray-500 mb-1">کل کامنت‌ها</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-amber-500">
        <p class="text-sm text-gray-500 mb-1">در انتظار</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['pending']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-green-500">
        <p class="text-sm text-gray-500 mb-1">پردازش‌شده</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['processed']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-red-500">
        <p class="text-sm text-gray-500 mb-1">رد شده (اسپم)</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['rejected']) }}</p>
    </div>

</div>

{{-- ═══════ بخش ۲: فیلتر ═══════ --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <form method="GET" action="{{ route('admin.comments.index') }}" class="flex flex-wrap gap-3 items-end">

        {{-- جستجو --}}
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-gray-500 mb-1">جستجو</label>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="متن کامنت یا ID..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
        </div>

        {{-- Status --}}
        <div class="w-40">
            <label class="block text-xs font-bold text-gray-500 mb-1">وضعیت</label>
            <select name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">همه</option>
                <option value="pending"    {{ request('status') === 'pending'    ? 'selected' : '' }}>در انتظار</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>در حال پردازش</option>
                <option value="processed"  {{ request('status') === 'processed'  ? 'selected' : '' }}>پردازش‌شده</option>
                <option value="rejected"   {{ request('status') === 'rejected'   ? 'selected' : '' }}>رد شده</option>
                <option value="skipped"    {{ request('status') === 'skipped'    ? 'selected' : '' }}>نادیده گرفته</option>
            </select>
        </div>

        {{-- دکمه‌ها --}}
        <div class="flex gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-gradient-to-l from-pink-500 to-purple-600 text-white text-sm font-bold rounded-lg hover:shadow-lg transition">
                فیلتر
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.comments.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition">
                    پاک کردن
                </a>
            @endif
        </div>

    </form>
</div>

{{-- ═══════ بخش ۳: جدول ═══════ --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">

    @if($comments->isEmpty())
        <div class="p-12 text-center">
            <div class="text-5xl mb-3">📭</div>
            <p class="text-gray-500">هیچ کامنتی پیدا نشد.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">#</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">کاربر</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">متن</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">وضعیت</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">زمان</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($comments as $comment)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-500">{{ $comment->id }}</td>

                            <td class="px-4 py-3">
                                <div class="text-xs">
                                    <div class="font-bold text-gray-700">
                                        {{ $comment->user?->username ?? '—' }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-gray-700 max-w-md">
                                <div class="truncate">{{ $comment->text }}</div>
                                @if($comment->is_spam)
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                        اسپم ({{ $comment->spam_score }})
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @php
                                    $statusMap = [
                                        'pending'    => ['label' => 'در انتظار',    'class' => 'bg-amber-100 text-amber-700'],
                                        'processing' => ['label' => 'در حال پردازش', 'class' => 'bg-blue-100 text-blue-700'],
                                        'processed'  => ['label' => 'پردازش‌شده',   'class' => 'bg-green-100 text-green-700'],
                                        'rejected'   => ['label' => 'رد شده',       'class' => 'bg-red-100 text-red-700'],
                                        'skipped'    => ['label' => 'نادیده',       'class' => 'bg-gray-100 text-gray-700'],
                                    ];
                                    $s = $statusMap[$comment->status] ?? ['label' => $comment->status, 'class' => 'bg-gray-100 text-gray-700'];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-bold {{ $s['class'] }}">
                                    {{ $s['label'] }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                {{ $comment->created_at?->format('Y/m/d H:i') }}
                            </td>

                            <td class="px-4 py-3">
                                <a href="{{ route('admin.comments.show', $comment->id) }}"
                                   class="text-purple-600 hover:text-purple-800 font-medium text-xs">
                                    جزئیات →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="px-4 py-4 border-t border-gray-200">
            {{ $comments->links() }}
        </div>
    @endif

</div>

@endsection