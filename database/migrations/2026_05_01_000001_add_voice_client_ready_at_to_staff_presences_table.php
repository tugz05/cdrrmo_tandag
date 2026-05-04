<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_presences', function (Blueprint $table) {
            $table->timestamp('voice_client_ready_at')->nullable()->after('last_heartbeat_at');
        });
    }

    public function down(): void
    {
        Schema::table('staff_presences', function (Blueprint $table) {
            $table->dropColumn('voice_client_ready_at');
        });
    }
};
