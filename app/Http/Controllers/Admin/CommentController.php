<?php
// app/Http/Controllers/Admin/CommentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CommentController extends Controller
{
    /**
     * لیست کامنت‌ها با فیلتر
     * 
     * URL: GET /admin/comments
     * URL: GET /admin/comments?status=pending&search=سلام
     */
    public function index(Request $request): View
    {
        // ═══ ۱. Query پایه ═══
        $query = Comment::query()->with(['user', 'post']);

        // ═══ ۲. فیلتر status ═══
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // ═══ ۳. فیلتر spam ═══
        if ($request->filled('is_spam')) {
            $query->where('is_spam', (bool) $request->input('is_spam'));
        }

        // ═══ ۴. جستجو ═══
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('text', 'like', "%{$search}%")
                  ->orWhere('sanitized_text', 'like', "%{$search}%")
                  ->orWhere('instagram_comment_id', 'like', "%{$search}%");
            });
        }

        // ═══ ۵. صفحه‌بندی ═══
        $perPage = (int) config('admin.ui.per_page', 20);
        $comments = $query->latest('id')->paginate($perPage)->withQueryString();

        // ═══ ۶. آمار ═══
        $stats = [
            'total'      => Comment::count(),
            'pending'    => Comment::where('status', 'pending')->count(),
            'processed'  => Comment::where('status', 'processed')->count(),
            'rejected'   => Comment::where('status', 'rejected')->count(),
            'skipped'    => Comment::where('status', 'skipped')->count(),
            'spam'       => Comment::where('is_spam', true)->count(),
            'today'      => Comment::whereDate('created_at', today())->count(),
        ];

        return view('admin.comments.index', compact('comments', 'stats'));
    }

    /**
     * نمایش جزئیات یه کامنت
     * 
     * URL: GET /admin/comments/{id}
     */
    public function show(int $id): View
    {
        $comment = Comment::with(['user', 'post', 'conversation', 'responses'])
            ->findOrFail($id);

        return view('admin.comments.show', compact('comment'));
    }
}