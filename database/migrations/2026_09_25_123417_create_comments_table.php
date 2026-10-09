<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_comment_id')->unique();
            $table->foreignId('instagram_post_id')
                  ->constrained('instagram_posts')
                  ->cascadeOnDelete();
            $table->foreignId('instagram_user_id')
                  ->constrained('instagram_users')
                  ->cascadeOnDelete();
            $table->string('parent_comment_id')->nullable();
            $table->text('text');
            $table->text('sanitized_text')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->boolean('is_spam')->default(false);
            $table->integer('spam_score')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('is_spam');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};