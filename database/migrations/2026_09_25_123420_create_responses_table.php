<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')
                  ->constrained('comments')
                  ->cascadeOnDelete();
            $table->foreignId('conversation_id')
                  ->nullable()
                  ->constrained('conversations')
                  ->nullOnDelete();
            $table->string('type', 20); // ai, faq, manual
            $table->text('content');
            $table->string('instagram_reply_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responses');
    }
};