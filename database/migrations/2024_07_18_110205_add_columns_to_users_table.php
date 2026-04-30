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
        Schema::table('users', function (Blueprint $table) {
            $table->string('fname')->nullable();
            $table->string('mname')->nullable();
            $table->string('lname')->nullable();
            $table->enum('suffix', ['Jr','Sr','I','II','III','IV','V'])->nullable();
            $table->datetime('confirmed_at')->nullable();
            $table->string('image')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('latitude', $precision = 10, $scale = 8)->nullable();
            $table->decimal('longitude', $precision = 12, $scale = 8)->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fname');
            $table->dropColumn('mname');
            $table->dropColumn('lname');
            $table->dropColumn('suffix');
            $table->dropColumn('confirmed_at');
            $table->dropColumn('image');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('phone');
            $table->dropSoftDeletes();
        });
    }
};
