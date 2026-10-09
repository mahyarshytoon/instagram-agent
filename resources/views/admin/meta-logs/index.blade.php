{{-- resources/views/admin/meta-logs/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'لاگ‌های Meta')
@section('page-title', '📝 لاگ‌های Meta')

@section('content')

{{-- ═══════ بخش ۱: آمار ═══════ --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-indigo-500">
        <p class="text-sm text-gray-500 mb-1">کل لاگ‌ها</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-purple-500">
        <p class="text-sm text-gray-500 mb-1">امروز</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['today']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-green-500">
        <p class="text-sm text-gray-500 mb-1">POST</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['post']) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-blue-500">
        <p class="text-sm text-gray-500 mb-1">GET</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['get']) }}</p>
    </div>

</div>

{{-- ═══════ بخش ۲: فیلتر ═══════ --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <form method="GET" action="{{ route('admin.meta-logs.index') }}" class="flex flex-wrap gap-3 items-end">

        {{-- جستجو --}}
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-gray-500 mb-1">جستجو</label>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="endpoint یا payload..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
        </div>

        {{-- متد --}}
        <div class="w-32">
            <label class="block text-xs font-bold text-gray-500 mb-1">متد</label>
            <select name="method"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">همه</option>
                <option value="POST" {{ request('method') === 'POST' ? 'selected' : '' }}>POST</option>
                <option value="GET"  {{ request('method') === 'GET'  ? 'selected' : '' }}>GET</option>
            </select>
        </div>

        {{-- دکمه‌ها --}}
        <div class="flex gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-gradient-to-l from-pink-500 to-purple-600 text-white text-sm font-bold rounded-lg hover:shadow-lg transition">
                فیلتر
            </button>
            @if(request()->hasAny(['search', 'method']))
                <a href="{{ route('admin.meta-logs.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition">
                    پاک کردن
                </a>
            @endif
        </div>

    </form>
</div>

{{-- ═══════ بخش ۳: جدول ═══════ --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">

    @if($logs->isEmpty())
        {{-- حالت خالی --}}
        <div class="p-12 text-center">
            <div class="text-5xl mb-3">📭</div>
            <p class="text-gray-500">هیچ لاگی پیدا نشد.</p>
        </div>
    @else
        {{-- جدول --}}
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-right font-bold text-gray-600">#</th>
                    <th class="px-4 py-3 text-right font-bold text-gray-600">متد</th>
                    <th class="px-4 py-3 text-right font-bold text-gray-600">Endpoint</th>
                    <th class="px-4 py-3 text-right font-bold text-gray-600">زمان</th>
                    <th class="px-4 py-3 text-right font-bold text-gray-600">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($logs as $log)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-gray-500">{{ $log->id }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-bold
                                {{ $log->method === 'POST' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $log->method }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 max-w-md truncate">
                            {{ $log->endpoint }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $log->created_at?->format('Y/m/d H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.meta-logs.show', $log->id) }}"
                               class="text-purple-600 hover:text-purple-800 font-medium text-xs">
                                جزئیات →
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- صفحه‌بندی --}}
        <div class="px-4 py-4 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
    @endif

</div>

@endsection