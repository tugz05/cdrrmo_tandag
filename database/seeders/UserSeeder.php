<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        try {
            // seed admin user
            $admin = User::firstOrCreate([
                'email' => 'admin@admin.com',
            ], [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);
            $admin->addRole('admin');

            $superAdmin = User::firstOrCreate([
                'email' => 'superadmin@cdrrmo.local',
            ], [
                'name' => 'Super Administrator',
                'email' => 'superadmin@cdrrmo.local',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);
            $superAdmin->syncRoles(['super_admin']);

            // seed verified user
            $citizen = User::firstOrCreate([
                'email' => 'user1@test.com',
            ], [
                'name' => 'User',
                'email' => 'user1@test.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);
            if (! $citizen->hasRole('user')) {
                $citizen->addRole('user');
            }

            // seed unverified user
            $citizen2 = User::firstOrCreate([
                'email' => 'user2@test.com',
            ], [
                'name' => 'User',
                'email' => 'user2@test.com',
                'password' => bcrypt('password'),
            ]);
            if (! $citizen2->hasRole('user')) {
                $citizen2->addRole('user');
            }
        } catch (\Exception) {
        }
    }
}
