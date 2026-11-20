<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phones and emails typed in before code sign-in existed were often never verified. Accounts that
     * have one now may still sign in with it; any account made later, or whose value changes, must verify.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('legacy_phone_sign_in')->default(false)->after('phone_verified_at');
            $table->boolean('legacy_email_sign_in')->default(false)->after('email_verified_at');
        });

        foreach (['phone' => 'legacy_phone_sign_in', 'email' => 'legacy_email_sign_in'] as $column => $flag) {
            DB::table('users')
                ->whereNotNull($column)
                ->whereRaw("TRIM({$column}) <> ''")
                ->update([$flag => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['legacy_phone_sign_in', 'legacy_email_sign_in']);
        });
    }
};
