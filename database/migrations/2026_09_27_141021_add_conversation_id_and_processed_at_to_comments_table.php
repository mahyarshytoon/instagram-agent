<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // اضافه کردن conversation_id بعد از instagram_user_id
            $table->foreignId('conversation_id')
                  ->nullable()
                  ->after('instagram_user_id')
                  ->constrained('conversations')
                  ->nullOnDelete();

            // اضافه کردن processed_at
            $table->timestamp('processed_at')
                  ->nullable()
                  ->after('status');

            // ایندکس برای جستجوی سریع‌تر
            $table->index('conversation_id');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropIndex(['conversation_id']);
            $table->dropIndex(['processed_at']);
            $table->dropColumn(['conversation_id', 'processed_at']);
        });
    }
};