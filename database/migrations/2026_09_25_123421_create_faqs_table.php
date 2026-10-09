<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->json('keywords');
            $table->text('question');
            $table->text('answer');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('hit_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};