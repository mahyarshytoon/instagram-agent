{{-- resources/views/admin/comments/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', "کامنت #{$comment->id}")
@section('page-title', "💬 کامنت #{$comment->id}")

@section('content')

{{-- دکمه بازگشت --}}
<div class="mb-4">
    <a href="{{ route('admin.comments.index') }}"
       class="text-sm text-purple-600 hover:text-purple-800 font-medium">
        ← بازگشت به لیست
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ═══════ ستون اصلی ═══════ --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- متن کامنت --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">📝 متن کامنت</h3>

            <div class="space-y-4">
                <div>
                    <p class="text-xs text-gray-500 mb-1">متن اصلی:</p>
                    <div class="p-3 bg-gray-50 rounded-lg text-sm" dir="auto">
                        {{ $comment->text }}
                    </div>
                </div>

                @if($comment->sanitized_text && $comment->sanitized_text !== $comment->text)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">متن پاک‌شده:</p>
                        <div class="p-3 bg-gray-50 rounded-lg text-sm" dir="auto">
                            {{ $comment->sanitized_text }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- پاسخ‌ها --}}
        @if($comment->responses->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">
                    🤖 پاسخ‌های ارسال‌شده ({{ $comment->responses->count() }})
                </h3>

                <div class="space-y-3">
                    @foreach($comment->responses as $response)
                        <div class="p-3 bg-green-50 border-r-4 border-green-500 rounded-lg">
                            <p class="text-sm text-gray-800">{{ $response->text ?? $response->response_text ?? '—' }}</p>
                            <p class="text-xs text-gray-500 mt-2">
                                {{ $response->created_at?->format('Y/m/d H:i') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    {{-- ═══════ ستون کناری ═══════ --}}
    <div class="space-y-6">

        {{-- اطلاعات پایه --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">📋 اطلاعات پایه</h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">ID داخلی:</span>
                    <span class="font-mono font-bold">{{ $comment->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">ID اینستاگرام:</span>
                    <span class="font-mono text-xs">{{ $comment->instagram_comment_id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">وضعیت:</span>
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
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">اسپم؟</span>
                    @if($comment->is_spam)
                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                            بله ({{ $comment->spam_score }})
                        </span>
                    @else
                        <span class="text-gray-700">خیر</span>
                    @endif
                </div>
                @if($comment->rejection_reason)
                    <div>
                        <span class="text-gray-500 block mb-1">دلیل رد:</span>
                        <span class="text-xs text-red-600">{{ $comment->rejection_reason }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- کاربر --}}
        @if($comment->user)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">👤 کاربر</h3>

                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500 text-xs">Username:</span>
                        <div class="font-bold">{{ $comment->user->username ?? '—' }}</div>
                    </div>
                    @if($comment->user->instagram_user_id ?? null)
                        <div>
                            <span class="text-gray-500 text-xs">ID اینستاگرام:</span>
                            <div class="font-mono text-xs">{{ $comment->user->instagram_user_id }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- پست --}}
        @if($comment->post)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">📷 پست</h3>

                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500 text-xs">ID:</span>
                        <div class="font-mono text-xs">{{ $comment->post->instagram_post_id ?? $comment->post->id }}</div>
                    </div>
                    @if($comment->post->caption ?? null)
                        <div>
                            <span class="text-gray-500 text-xs">کپشن:</span>
                            <div class="text-xs line-clamp-3">{{ $comment->post->caption }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- زمان‌ها --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">🕐 زمان‌ها</h3>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-500">ایجاد:</span>
                    <span>{{ $comment->created_at?->format('Y/m/d H:i:s') }}</span>
                </div>
                @if($comment->processed_at)
                    <div class="flex justify-between">
                        <span class="text-gray-500">پردازش:</span>
                        <span>{{ $comment->processed_at->format('Y/m/d H:i:s') }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-500">آپدیت:</span>
                    <span>{{ $comment->updated_at?->format('Y/m/d H:i:s') }}</span>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection