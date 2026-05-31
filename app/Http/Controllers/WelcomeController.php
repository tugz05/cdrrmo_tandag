<?php

namespace App\Http\Controllers;

use App\Enums\PostTypeEnum;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'latestNews' => $this->latestPosts(PostTypeEnum::NEWS),
            'latestSafetyTips' => $this->latestPosts(PostTypeEnum::SAFETY_TIPS),
            'latestPreparedness' => $this->latestPosts(PostTypeEnum::EMERGENCY_PREPAREDNESS),
            'publicStats' => [
                'reportsRecorded' => Report::count(),
                'channelsMessages' => Report::where('type', 'Message')->count(),
                'channelsCalls' => Report::where('type', 'Call')->count(),
            ],
            'mobileApp' => [
                'android' => config('cdrrmo.mobile_app_android_url'),
                'ios' => config('cdrrmo.mobile_app_ios_url'),
                'direct' => route('app.download'),
            ],
        ]);
    }

    /**
     * @return array<int, array{id:int, title:string, excerpt:string, type:string, href:string}>
     */
    private function latestPosts(string $type, int $limit = 5): array
    {
        return Post::query()
            ->where('type', $type)
            ->where('is_published', true)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => Str::limit(
                    trim(preg_replace('/\s+/', ' ', strip_tags((string) $post->content))),
                    150
                ),
                'type' => $post->type,
                'href' => $this->guestHref($post),
            ])
            ->values()
            ->all();
    }

    private function guestHref(Post $post): string
    {
        return match ($post->type) {
            PostTypeEnum::NEWS => route('guest.news.index', $post->id),
            PostTypeEnum::SAFETY_TIPS => route('guest.safety-tips.index', $post->id),
            PostTypeEnum::EMERGENCY_PREPAREDNESS => route('guest.emergency-preparedness.index', $post->id),
            default => '#',
        };
    }
}
