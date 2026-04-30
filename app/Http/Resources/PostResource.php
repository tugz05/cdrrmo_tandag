<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bg_image' => $this->resolvePublicImageUrl($this->bg_image),
            'title' => $this->title,
            'content' => $this->content,
            'created_at' => Carbon::parse($this->created_at)->diffForHumans(),
        ];
    }

    /**
     * Full URL for mobile/web clients (handles stored paths and absolute URLs).
     */
    private function resolvePublicImageUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }
}
