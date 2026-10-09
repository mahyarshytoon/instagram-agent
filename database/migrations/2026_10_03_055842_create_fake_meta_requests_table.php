<?php
// database/migrations/xxxx_create_fake_meta_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fake_meta_requests', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();

            $table->index('endpoint');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fake_meta_requests');
    }
};