<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->nullable();
            $table->json('payload');
            $table->boolean('signature_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->text('error')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('processed');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};