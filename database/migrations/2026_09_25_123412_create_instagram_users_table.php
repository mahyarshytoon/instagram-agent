<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_users', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_id')->unique();
            $table->string('username')->nullable();
            $table->string('full_name')->nullable();
            $table->text('profile_pic_url')->nullable();
            $table->boolean('is_follower')->default(false);
            $table->integer('score')->default(0);
            $table->integer('total_comments')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('username');
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_users');
    }
};