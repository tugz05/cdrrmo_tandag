<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('rescue_status')->default('pending')->after('status');
            $table->foreignId('rescuer_user_id')->nullable()->constrained('users')->nullOnDelete()->after('rescue_status');
            $table->timestamp('rescue_started_at')->nullable()->after('rescuer_user_id');
            $table->timestamp('rescue_arrived_at')->nullable()->after('rescue_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['rescuer_user_id']);
            $table->dropColumn(['rescue_status', 'rescuer_user_id', 'rescue_started_at', 'rescue_arrived_at']);
        });
    }
};
