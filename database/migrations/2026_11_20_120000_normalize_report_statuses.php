<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Answering a call used to write "in_progress" and completing a rescue "completed",
     * which the admin pages didn't recognise. They now use the admin's own values.
     */
    public function up(): void
    {
        DB::table('reports')->whereRaw("LOWER(status) = 'in_progress'")->update(['status' => 'In Progress']);
        DB::table('reports')->whereRaw("LOWER(status) = 'completed'")->update(['status' => 'Rescued']);
    }

    /**
     * Nothing to undo: the old values meant the same thing and nothing depended on them.
     */
    public function down(): void {}
};
