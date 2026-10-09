<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_admin_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            
            // کلید یکتا — مثل "ai.model" یا "spam.banned_words"
            $table->string('key')->unique();
            
            // مقدار — می‌تونه متن، عدد، JSON باشه
            $table->text('value')->nullable();
            
            // گروه — برای دسته‌بندی تو پنل (ai، spam، meta، rate_limit، general)
            $table->string('group')->default('general')->index();
            
            // توضیح انسانی — برای نمایش تو پنل
            $table->string('description')->nullable();
            
            // نوع داده — برای cast خودکار (string, int, bool, array)
            $table->string('type')->default('string');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};