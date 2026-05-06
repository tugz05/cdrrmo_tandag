<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('app_role', 32)->default('citizen')->after('email');
        });

        if (! Schema::hasTable('role_user') || ! Schema::hasTable('roles')) {
            return;
        }

        $staffUserIds = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.name', 'staff')
            ->where('role_user.user_type', User::class)
            ->pluck('role_user.user_id')
            ->unique()
            ->values()
            ->all();

        if ($staffUserIds !== []) {
            DB::table('users')->whereIn('id', $staffUserIds)->update(['app_role' => 'staff']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('app_role');
        });
    }
};
