<?php
// app/Http/Controllers/Admin/MetaLogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FakeMetaRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetaLogController extends Controller
{
    /**
     * لیست همه لاگ‌ها + فیلتر + صفحه‌بندی
     * 
     * URL: GET /admin/meta-logs
     */
    public function index(Request $request): View
    {
        // ═══ ۱. Query پایه (هنوز اجرا نمی‌شه) ═══
        $query = FakeMetaRequest::query();

        // ═══ ۲. فیلتر جستجو ═══
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('endpoint', 'like', "%{$search}%")
                  ->orWhere('payload', 'like', "%{$search}%");
            });
        }

        // ═══ ۳. فیلتر متد ═══
        if ($method = $request->input('method')) {
            $query->where('method', strtoupper($method));
        }

        // ═══ ۴. صفحه‌بندی ═══
        $perPage = (int) config('admin.ui.per_page', 20);
        $logs = $query->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // ═══ ۵. آمار برای بالای صفحه ═══
        $stats = [
            'total' => FakeMetaRequest::count(),
            'today' => FakeMetaRequest::whereDate('created_at', today())->count(),
            'post'  => FakeMetaRequest::where('method', 'POST')->count(),
            'get'   => FakeMetaRequest::where('method', 'GET')->count(),
        ];

        return view('admin.meta-logs.index', compact('logs', 'stats'));
    }

    /**
     * نمایش جزئیات یه لاگ
     * 
     * URL: GET /admin/meta-logs/{id}
     */
    public function show(int $id): View
    {
        $log = FakeMetaRequest::findOrFail($id);

        return view('admin.meta-logs.show', compact('log'));
    }
}