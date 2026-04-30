<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('details')->nullable();
            $table->string('status'); 
            $table->datetime('call_started_at')->nullable();
            $table->datetime('call_ended_at')->nullable();
            $table->string('type')->nullable();
            $table->string('address')->nullable();
            $table->string('reported_by')->nullable();
            $table->string('reporters_address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
