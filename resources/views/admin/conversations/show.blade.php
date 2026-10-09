{{-- resources/views/admin/conversations/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', "مکالمه #{$conversation->id}")
@section('page-title', "🎯 مکالمه #{$conversation->id}")

@section('content')

{{-- دکمه بازگشت --}}
<div class="mb-4">
    <a href="{{ route('admin.conversations.index') }}"
       class="text-sm text-purple-600 hover:text-purple-800 font-medium">
        ← بازگشت به لیست
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ═══════ ستون اصلی: Thread پیام‌ها ═══════ --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">💬 پیام‌ها ({{ $conversation->messages->count() }})</h3>
                @php
                    $statusMap = [
                        'active' => ['label' => 'فعال',      'class' => 'bg-green-100 text-green-700'],
                        'closed' => ['label' => 'بسته‌شده',  'class' => 'bg-gray-100 text-gray-700'],
                    ];
                    $s = $statusMap[$conversation->status] ?? ['label' => $conversation->status, 'class' => 'bg-gray-100 text-gray-700'];
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $s['class'] }}">
                    {{ $s['label'] }}
                </span>
            </div>

            {{-- Messages --}}
            @if($conversation->messages->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    هنوز پیامی تو این مکالمه نیست.
                </div>
            @else
                <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto bg-gray-50">

                    @foreach($conversation->messages as $message)
                        @php
                            $isUser = $message->role === 'user';
                            $isAssistant = $message->role === 'assistant';
                        @endphp

                        <div class="flex {{ $isUser ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[75%]">

                                {{-- Header (role) --}}
                                <div class="text-xs text-gray-500 mb-1 {{ $isUser ? 'text-left' : 'text-right' }}">
                                    @if($isUser)
                                        👤 کاربر
                                    @elseif($isAssistant)
                                        🤖 ربات
                                    @else
                                        ⚙️ سیستم
                                    @endif
                                    <span class="text-gray-400 mx-1">•</span>
                                    {{ $message->created_at?->format('H:i') }}
                                </div>

                                {{-- Bubble --}}
                                <div class="px-4 py-3 rounded-2xl text-sm
                                    {{ $isUser
                                        ? 'bg-white border border-gray-200 text-gray-800 rounded-tl-sm'
                                        : 'bg-gradient-to-l from-pink-500 to-purple-600 text-white rounded-tr-sm' }}">
                                    <div class="whitespace-pre-wrap" dir="auto">{{ $message->content }}</div>
                                </div>

                                {{-- Meta (model + tokens) --}}
                                @if($message->ai_model || $message->ai_tokens)
                                    <div class="text-xs text-gray-400 mt-1 {{ $isUser ? 'text-left' : 'text-right' }}">
                                        @if($message->ai_model)
                                            <span class="font-mono">{{ $message->ai_model }}</span>
                                        @endif
                                        @if($message->ai_tokens)
                                            <span class="mx-1">•</span>
                                            <span>{{ $message->ai_tokens }} توکن</span>
                                        @endif
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach

                </div>
            @endif

        </div>
    </div>

    {{-- ═══════ ستون کناری ═══════ --}}
    <div class="space-y-6">

        {{-- اطلاعات مکالمه --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">📋 اطلاعات مکالمه</h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">ID:</span>
                    <span class="font-mono font-bold">{{ $conversation->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">تعداد پیام:</span>
                    <span class="font-bold">{{ $conversation->message_count }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">شروع:</span>
                    <span class="text-xs">{{ $conversation->started_at?->format('Y/m/d H:i') ?? '—' }}</span>
                </div>
                @if($conversation->last_message_at)
                    <div class="flex justify-between">
                        <span class="text-gray-500">آخرین پیام:</span>
                        <span class="text-xs">{{ $conversation->last_message_at->diffForHumans() }}</span>
                    </div>
                @endif
                @if($conversation->closed_at)
                    <div class="flex justify-between">
                        <span class="text-gray-500">بسته‌شده:</span>
                        <span class="text-xs">{{ $conversation->closed_at->format('Y/m/d H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- کاربر --}}
        @if($conversation->user)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">👤 کاربر</h3>

                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500 text-xs">Username:</span>
                        <div class="font-bold">{{ $conversation->user->username ?? '—' }}</div>
                    </div>
                    @if($conversation->user->instagram_user_id ?? null)
                        <div>
                            <span class="text-gray-500 text-xs">ID اینستاگرام:</span>
                            <div class="font-mono text-xs break-all">{{ $conversation->user->instagram_user_id }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- پست --}}
        @if($conversation->post)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">📷 پست</h3>

                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500 text-xs">ID:</span>
                        <div class="font-mono text-xs break-all">
                            {{ $conversation->post->instagram_post_id ?? $conversation->post->id }}
                        </div>
                    </div>
                    @if($conversation->post->caption ?? null)
                        <div>
                            <span class="text-gray-500 text-xs">کپشن:</span>
                            <div class="text-xs line-clamp-3">{{ $conversation->post->caption }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>

</div>

@endsection