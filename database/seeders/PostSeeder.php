<?php

namespace Database\Seeders;

use App\Enums\JHelper;
use App\Enums\PostTypeEnum;
use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {
            Post::create([
                'user_id' => null,
                // Placeholder so seeded posts appear on public API (must be non-empty when published)
                'bg_image' => 'https://placehold.co/1200x630/e0f2fe/075985/?text=CDRRMO+Post',
                'title' => fake()->realText(maxNbChars: 50),
                'is_published' => true,
                'type' => JHelper::getRandomValue(PostTypeEnum::all()),
                'content' => fake()->sentence,
            ]);
        }
    }
}
