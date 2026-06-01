<?php

namespace App\Services;

use App\Enums\PostTypeEnum;
use App\Interfaces\NewsInterface;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PostService implements NewsInterface
{
    public function index($type = null)
    {
        return [
            'news' => Post::when(is_null($type), function ($query) {
                $query->whereType(PostTypeEnum::NEWS);
            }, function ($query) use ($type) {
                $query->whereType($type);
            })->latest()->get(),
            'active_tab' => $type,
        ];
    }

    public function create()
    {
        return [
            'types' => PostTypeEnum::all(),
        ];
    }

    public function store(Request $request): mixed
    {
        $validated = $request->validated();
        $data = collect($validated)
            ->except(['bg_image', 'is_published'])
            ->all();

        $data['user_id'] = auth()->id();

        if ($request->hasFile('bg_image')) {
            $data['bg_image'] = 'storage/'.$request->file('bg_image')->store('posts', 'public');
        } elseif (! empty($data['id'])) {
            $existing = Post::find($data['id']);
            if ($existing?->bg_image) {
                $data['bg_image'] = $existing->bg_image;
            }
        }

        $id = $data['id'] ?? null;
        if ($id === '' || $id === null) {
            unset($data['id']);
            $post = Post::create($data);
        } else {
            $post = Post::updateOrCreate(
                ['id' => $id],
                $data
            );
        }

        return to_route('posts.edit', $post->id, 303);
    }

    public function updateTitle($validatedData)
    {
        Post::findOrFail($validatedData['id'])
            ->update(['title' => $validatedData['title']]);
    }

    public function updateType($validatedData)
    {
        Post::findOrFail($validatedData['id'])
            ->update(['type' => $validatedData['type']]);
    }

    public function edit(Post $post)
    {
        return [
            'post' => $post,
            'types' => PostTypeEnum::all(),
        ];
    }

    public function updateStatus($id)
    {
        $news = Post::find($id, ['id', 'is_published']);
        $is_published = $news->is_published ? false : true;
        $news->update(['is_published' => $is_published]);
    }

    public function destroy($id)
    {
        Post::destroy($id);
    }

    public function restore($id)
    {
        Post::withTrashed()->find($id)->restore();
    }

    public function publish($id)
    {
        $post = Post::findOrFail($id);
        $willPublish = ! $post->is_published;

        if ($willPublish && ! self::postHasFeaturedImage($post)) {
            throw ValidationException::withMessages([
                'bg_image' => 'Add a featured image before publishing this post.',
            ]);
        }

        $post->is_published = ! $post->is_published;
        $post->save();
    }

    private static function postHasFeaturedImage(Post $post): bool
    {
        $path = $post->bg_image;

        return is_string($path) && trim($path) !== '';
    }
}
