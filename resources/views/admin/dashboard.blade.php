{{-- resources/views/admin/dashboard.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'داشبورد')
@section('page-title', '📊 داشبورد')

@section('content')

{{-- آمار کلی --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-indigo-500">
        <p class="text-sm text-gray-500 mb-1">کل لاگ‌ها</p>
        <p class="text-2xl font-bold text-gray-800">
            {{ number_format(\App\Models\FakeMetaRequest::count()) }}
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-purple-500">
        <p class="text-sm text-gray-500 mb-1">امروز</p>
        <p class="text-2xl font-bold text-gray-800">
            {{ number_format(\App\Models\FakeMetaRequest::whereDate('created_at', today())->count()) }}
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-green-500">
        <p class="text-sm text-gray-500 mb-1">کامنت‌ها</p>
        <p class="text-2xl font-bold text-gray-800">
            {{ number_format(\App\Models\Comment::count()) }}
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-pink-500">
        <p class="text-sm text-gray-500 mb-1">پاسخ‌ها</p>
        <p class="text-2xl font-bold text-gray-800">
            {{ number_format(\App\Models\Response::count()) }}
        </p>
    </div>
</div>

{{-- آخرین لاگ‌ها --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="font-bold text-gray-800">🔥 آخرین لاگ‌ها</h3>
        <a href="{{ route('admin.meta-logs.index') }}"
           class="text-sm text-purple-600 hover:text-purple-800">
            مشاهده همه →
        </a>
    </div>

    @php
        $recentLogs = \App\Models\FakeMetaRequest::latest('id')->limit(5)->get();
    @endphp

    @if($recentLogs->isEmpty())
        <div class="p-8 text-center text-gray-500">
            هنوز لاگی ثبت نشده.
        </div>
    @else
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($recentLogs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-500">#{{ $log->id }}</td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-bold
                                {{ $log->method === 'POST' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $log->method }}
                            </span>
                        </td>
                        <td class="px-6 py-3 font-mono text-xs text-gray-700 truncate max-w-md">
                            {{ $log->endpoint }}
                        </td>
                        <td class="px-6 py-3 text-xs text-gray-500">
                            {{ $log->created_at?->diffForHumans() }}
                        </td>
                        <td class="px-6 py-3">
                            <a href="{{ route('admin.meta-logs.show', $log->id) }}"
                               class="text-purple-600 text-xs">مشاهده</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection