<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_device_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 16);
            $table->string('fcm_token', 512);
            $table->timestamps();

            $table->unique(['user_id', 'platform']);
            $table->index('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_fcm_tokens');
    }
};
