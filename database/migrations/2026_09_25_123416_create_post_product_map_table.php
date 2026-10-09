<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_product_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_post_id')
                  ->constrained('instagram_posts')
                  ->cascadeOnDelete();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['instagram_post_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_product_map');
    }
};