<?php

namespace App\Observers;

use App\Models\Post;
use App\Traits\JSharedTrait;

class PostObserver
{
    use JSharedTrait;

    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        $this->toastSuccess('Changes have been saved.');
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->toastDeleted('Post has been successfully deleted.');
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        //
    }
}
