<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                  ->constrained('conversations')
                  ->cascadeOnDelete();
            $table->string('role', 20); // user, assistant, system
            $table->text('content');
            $table->string('instagram_comment_id')->nullable();
            $table->string('ai_model', 50)->nullable();
            $table->integer('ai_tokens')->nullable();
            $table->timestamps();

            $table->index('role');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};