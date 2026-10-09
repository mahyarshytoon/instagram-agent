<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->name }} — InstagramShop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        body { font-family: Vazirmatn, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

{{-- Top bar --}}
<header class="bg-white border-b sticky top-0 z-10">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
        <a href="{{ route('demo.index') }}" class="text-gray-600 hover:text-gray-900 text-2xl leading-none">→</a>
        <h1 class="text-lg font-bold text-gray-800">پست</h1>
    </div>
</header>

<div class="max-w-3xl mx-auto bg-white my-6 rounded-2xl shadow-md overflow-hidden">

    {{-- عکس پست --}}
    <div class="aspect-square bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 flex items-center justify-center">
        <div class="text-center">
            <div class="text-9xl mb-4">🛍️</div>
            <p class="text-lg text-gray-600 font-bold">{{ $product->name }}</p>
            <p class="text-sm text-gray-500 mt-2">
                {{ number_format($product->price) }} تومان
                @if($product->old_price)
                    <span class="line-through text-xs">({{ number_format($product->old_price) }})</span>
                @endif
            </p>
        </div>
    </div>

    {{-- اطلاعات پیج --}}
    <div class="px-4 py-3 border-b flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-pink-400 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
            IS
        </div>
        <div class="flex-1">
            <p class="font-bold text-sm text-gray-800">instagram_shop</p>
            <p class="text-xs text-gray-500">{{ $product->name }}</p>
        </div>
        <button class="text-indigo-600 text-sm font-bold">Follow</button>
    </div>

    {{-- Caption --}}
    @if($product->description)
        <div class="px-4 py-3 border-b">
            <p class="text-sm text-gray-700">
                <span class="font-bold">instagram_shop</span>
                {{ $product->description }}
            </p>
        </div>
    @endif

    {{-- کامنت‌ها --}}
    <div id="comments" class="px-4 py-4 space-y-4 max-h-[500px] overflow-y-auto">

        @forelse($comments as $comment)
            {{-- کامنت کاربر --}}
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 shrink-0">
                    {{ substr($comment->instagramUser->username ?? '?', 0, 1) }}
                </div>
                <div class="flex-1">
                    <p class="text-sm">
                        <span class="font-bold text-gray-800">{{ $comment->instagramUser->username ?? 'user' }}</span>
                        <span class="text-gray-700 mr-2">{{ $comment->text }}</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ $comment->created_at->diffForHumans() }}</p>

                    {{-- جواب AI (اگه هست) --}}
                    @if($comment->is_spam)
                        <div class="mt-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2 text-xs text-red-700">
                            🚫 این کامنت پاک شد (محتوای نامناسب)
                        </div>
                    @elseif($comment->responses->isNotEmpty())
                        @php $aiResponse = $comment->responses->last(); @endphp
                        <div class="mt-2 flex gap-2">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-pink-400 to-purple-500 flex items-center justify-center text-white text-[10px] font-bold shrink-0">
                                IS
                            </div>
                            <div class="flex-1 bg-gray-50 rounded-xl px-3 py-2">
                                <p class="text-sm">
                                    <span class="font-bold text-gray-800">instagram_shop</span>
                                    <span class="text-gray-700 mr-2">{{ $aiResponse->content }}</span>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">🤖 AI</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-center text-gray-400 py-8 text-sm">
                هنوز کامنتی نیست. اولین کامنت رو تو بنویس!
            </p>
        @endforelse

    </div>

    {{-- باکس کامنت جدید --}}
    <div class="border-t px-4 py-3 bg-white sticky bottom-0">
        <form id="comment-form" onsubmit="submitComment(event)" class="flex gap-2">
            <input type="hidden" id="product-id" value="{{ $product->id }}">
            <input
                type="text"
                id="comment-text"
                placeholder="کامنتت رو بنویس..."
                class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
                maxlength="500"
                autocomplete="off">
            <button
                type="submit"
                id="submit-btn"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2 rounded-full text-sm disabled:opacity-50">
                ارسال
            </button>
        </form>
    </div>

</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

async function submitComment(e) {
    e.preventDefault();

    const btn = document.getElementById('submit-btn');
    const input = document.getElementById('comment-text');
    const originalText = btn.textContent;

    btn.disabled = true;
    btn.textContent = '...';

    const payload = {
        product_id: document.getElementById('product-id').value,
        text: input.value,
    };

    try {
        const res = await fetch('/demo/comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (!data.success) {
            alert('خطا: ' + (data.error || 'ناشناخته'));
            return;
        }

        addUserComment(data.comment.text);

        if (data.spam) {
            addSpamComment(data.reply);
        } else {
            addAIComment(data.reply);
        }

        input.value = '';
        document.getElementById('comments').scrollTop = 999999;

    } catch (err) {
        alert('خطای شبکه: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

function addUserComment(text) {
    const box = document.getElementById('comments');
    const div = document.createElement('div');
    div.className = 'flex gap-3';
    div.innerHTML = `
        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 shrink-0">ش</div>
        <div class="flex-1">
            <p class="text-sm">
                <span class="font-bold text-gray-800">شما</span>
                <span class="text-gray-700 mr-2">${escapeHtml(text)}</span>
            </p>
            <p class="text-xs text-gray-400 mt-1">همین الان</p>
        </div>
    `;
    box.appendChild(div);
}

function addAIComment(text) {
    const box = document.getElementById('comments');
    const div = document.createElement('div');
    div.className = 'flex gap-3 mr-11';
    div.innerHTML = `
        <div class="flex-1 bg-gray-50 rounded-xl px-3 py-2">
            <p class="text-sm">
                <span class="font-bold text-gray-800">instagram_shop</span>
                <span class="text-gray-700 mr-2">${escapeHtml(text)}</span>
            </p>
            <p class="text-xs text-gray-400 mt-1">🤖 AI</p>
        </div>
    `;
    box.appendChild(div);
}

function addSpamComment(text) {
    const box = document.getElementById('comments');
    const div = document.createElement('div');
    div.className = 'flex gap-3 mr-11';
    div.innerHTML = `
        <div class="flex-1 bg-red-50 border border-red-200 rounded-xl px-3 py-2">
            <p class="text-sm text-red-700">🚫 این کامنت به دلیل محتوای نامناسب پاک شد.</p>
        </div>
    `;
    box.appendChild(div);
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}
</script>

</body>
</html>