<?php
// routes/web.php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\Admin\MetaLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\ConversationController;
use Illuminate\Support\Facades\Route;

// ═══ Root ═══
Route::get('/', fn() => redirect('/demo'));

// ═══ Demo ═══
Route::prefix('demo')->group(function () {
    Route::get('/',               [DemoController::class, 'index'])->name('demo.index');
    Route::get('/post/{product}', [DemoController::class, 'show'])->name('demo.post');
    Route::post('/comment',       [DemoController::class, 'store'])->name('demo.comment');
});

// ═══ Admin Panel ═══
$adminPrefix = config('admin.route.prefix', 'admin');

Route::prefix($adminPrefix)->name('admin.')->group(function () {

    // ─── Login (بدون middleware) ───
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    // ─── Protected Routes ───
    Route::middleware(['admin.auth'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');

        // 🆕 Meta Logs
        Route::get('/meta-logs',      [MetaLogController::class, 'index'])->name('meta-logs.index');
        Route::get('/meta-logs/{id}', [MetaLogController::class, 'show'])->name('meta-logs.show');
		
		// ⚙️ Settings
        Route::get('/settings',  [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
		
		// 💬 Comments
        Route::get('/comments',      [CommentController::class, 'index'])->name('comments.index');
        Route::get('/comments/{id}', [CommentController::class, 'show'])->name('comments.show');
		
		// 🎯 Conversations
        Route::get('/conversations',      [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/{id}', [ConversationController::class, 'show'])->name('conversations.show');
		
    });
});