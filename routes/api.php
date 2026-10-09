<?php
// routes/api.php

use App\Http\Controllers\Api\InstagramWebhookController;
use App\Http\Controllers\Fake\FakeMetaController;
use Illuminate\Support\Facades\Route;

// ========== Webhook واقعی ==========
Route::prefix('instagram')->group(function () {
    Route::get('/webhook', [InstagramWebhookController::class, 'verify'])
        ->name('instagram.webhook.verify');
    Route::post('/webhook', [InstagramWebhookController::class, 'handle'])
        ->name('instagram.webhook.handle');
});

// ========== 🆕 FakeMetaServer (فقط local/testing) ==========
if (app()->environment('local', 'testing')) {
    Route::post('fake-meta/{version}/{commentId}/replies', [FakeMetaController::class, 'replyToComment'])
        ->where('version', 'v[0-9]+\.[0-9]+')
        ->where('commentId', '[A-Za-z0-9_]+')
        ->name('fake-meta.replies');

    Route::get('fake-meta/_logs', [FakeMetaController::class, 'logs'])
        ->name('fake-meta.logs');
}