<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('suffix');
            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }
            $table->timestamp('address_updated_at')->nullable()->after('address');
        });

        // Phone-only accounts have no email. The unique index stays: it allows any number of NULLs.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Phone-only accounts would break a NOT NULL email, so it only goes back when none exist.
        if (! DB::table('users')->whereNull('email')->exists()) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birthdate', 'phone_verified_at', 'address_updated_at']);
        });
    }
};
