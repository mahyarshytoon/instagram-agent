<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_ai_meta_to_responses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            // مدل AI که پاسخ رو تولید کرده
            $table->string('ai_model', 100)->nullable()->after('type');
            
            // تعداد توکن ورودی (prompt + history)
            $table->integer('input_tokens')->nullable()->after('ai_model');
            
            // تعداد توکن خروجی (پاسخ)
            $table->integer('output_tokens')->nullable()->after('input_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn(['ai_model', 'input_tokens', 'output_tokens']);
        });
    }
};