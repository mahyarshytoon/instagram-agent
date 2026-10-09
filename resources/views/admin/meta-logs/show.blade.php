{{-- resources/views/admin/meta-logs/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'جزئیات لاگ #' . $log->id)
@section('page-title', '📝 جزئیات لاگ #' . $log->id)

@section('content')

{{-- دکمه بازگشت --}}
<div class="mb-4">
    <a href="{{ route('admin.meta-logs.index') }}"
       class="text-sm text-purple-600 hover:text-purple-800">
        ← بازگشت به لیست
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ═══ اطلاعات پایه ═══ --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b">📋 اطلاعات پایه</h3>

        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">شناسه:</dt>
                <dd class="font-mono font-bold">#{{ $log->id }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">متد:</dt>
                <dd>
                    <span class="px-2 py-1 rounded-full text-xs font-bold
                        {{ $log->method === 'POST' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ $log->method }}
                    </span>
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">زمان:</dt>
                <dd class="font-mono">{{ $log->created_at?->format('Y/m/d H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 mb-1">Endpoint:</dt>
                <dd class="font-mono text-xs bg-gray-50 p-2 rounded break-all">
                    {{ $log->endpoint }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- ═══ پاسخ ═══ --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b">✅ پاسخ</h3>

        @if($log->response)
            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-auto max-h-64" dir="ltr">{{ json_encode($log->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @else
            <p class="text-gray-400 text-sm">پاسخی ثبت نشده.</p>
        @endif
    </div>

    {{-- ═══ Payload ═══ --}}
    <div class="bg-white rounded-xl shadow-sm p-6 lg:col-span-2">
        <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b">📦 Payload (داده ارسالی)</h3>

        @if($log->payload)
            <pre class="bg-gray-900 text-yellow-400 p-4 rounded-lg text-xs overflow-auto max-h-64" dir="ltr">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @else
            <p class="text-gray-400 text-sm">Payload خالی.</p>
        @endif
    </div>

    {{-- ═══ Headers ═══ --}}
    <div class="bg-white rounded-xl shadow-sm p-6 lg:col-span-2">
        <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b">📨 Headers</h3>

        @if($log->headers)
            <pre class="bg-gray-900 text-blue-400 p-4 rounded-lg text-xs overflow-auto max-h-64" dir="ltr">{{ json_encode($log->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @else
            <p class="text-gray-400 text-sm">Header ثبت نشده.</p>
        @endif
    </div>

</div>

@endsection