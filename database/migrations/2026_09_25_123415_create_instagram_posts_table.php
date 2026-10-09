<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_post_id')->unique();
            $table->text('caption')->nullable();
            $table->text('media_url')->nullable();
            $table->string('media_type', 50)->nullable(); // IMAGE, VIDEO, CAROUSEL
            $table->text('permalink')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};