<?php

namespace Database\Seeders;

use App\Enums\JHelper;
use App\Enums\ReportStatusEnum;
use App\Enums\ReportTypeEnum;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::take(20)->get();
        foreach($users as $user) {
            Report::firstOrCreate([
                'user_id' => $user->id,
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),    
                'details' => fake()->realText(maxNbChars: 50),
                'call_started_at' => now(),
                'call_ended_at' => now(),
                'status' => JHelper::getRandomValue(ReportStatusEnum::all()),
                'type' => JHelper::getRandomValue(ReportTypeEnum::all()),
            ]);
        }
    }

}


