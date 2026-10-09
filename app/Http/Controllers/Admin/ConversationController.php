<?php
// app/Http/Controllers/Admin/ConversationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    /**
     * لیست مکالمات با فیلتر
     * 
     * URL: GET /admin/conversations
     * URL: GET /admin/conversations?status=active&search=ali
     */
    public function index(Request $request): View
    {
        // ═══ ۱. Query پایه ═══
        $query = Conversation::query()->with(['user', 'post']);

        // ═══ ۲. فیلتر status ═══
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // ═══ ۳. فیلتر کاربر (با username) ═══
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%");
                  });
            });
        }

        // ═══ ۴. صفحه‌بندی ═══
        $perPage = (int) config('admin.ui.per_page', 20);
        $conversations = $query->latest('last_message_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // ═══ ۵. آمار ═══
        $stats = [
            'total'        => Conversation::count(),
            'active'       => Conversation::where('status', 'active')->count(),
            'closed'       => Conversation::where('status', 'closed')->count(),
            'today'        => Conversation::whereDate('created_at', today())->count(),
            'total_msgs'   => \App\Models\Message::count(),
        ];

        return view('admin.conversations.index', compact('conversations', 'stats'));
    }

    /**
     * نمایش thread یه مکالمه
     * 
     * URL: GET /admin/conversations/{id}
     */
    public function show(int $id): View
    {
        $conversation = Conversation::with([
            'user',
            'post',
            'messages' => fn ($q) => $q->orderBy('created_at'),
        ])->findOrFail($id);

        return view('admin.conversations.show', compact('conversation'));
    }
}