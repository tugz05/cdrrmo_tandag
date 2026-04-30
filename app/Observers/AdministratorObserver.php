<?php

namespace App\Observers;

use App\Enums\UserTypeEnum;
use App\Models\User;
use App\Traits\JSharedTrait;

class AdministratorObserver
{
    use JSharedTrait;
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if (!$user->hasRole([UserTypeEnum::ADMIN, UserTypeEnum::SUPER_ADMIN]))
            return;

        $this->toastSuccess("<b>$user->name</b> has been successfully updated.");
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
