<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'admin',
        ]);
        Role::firstOrCreate([
            'name' => 'super_admin',
        ]);
        Role::firstOrCreate([
            'name' => 'user',
        ]);
        Role::firstOrCreate([
            'name' => 'staff',
            'display_name' => 'Staff / Rescuer',
            'description' => 'Mobile app staff and field responders (not web dashboard admins).',
        ]);
    }
}
