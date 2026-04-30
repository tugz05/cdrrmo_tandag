<?php

namespace App\Traits;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enums\JToastEnum;

trait JSharedTrait
{
    // refactor this trait.
    
    public function withUndo($link)
    {
        return "<Link :href=\"$link\"><b>UNDO</b></Link";
    }

    // SUCCESS
    public function toastSuccess(string $message = 'Data has been successfully saved.')
    {
        // session()->flash('undo', 'dashboard.index');
        session()->flash(JToastEnum::RESTORE, '');
        return session()->flash(JToastEnum::SUCCESS, $message);
    }

    public function toastSaved(string $message = 'Data has been successfully saved.')
    {
        return session()->flash(JToastEnum::SUCCESS, $message);
    }

    public function toastDeleted(
        string $message = 'Data has been deleted successfully.',
        string $restoreRoute = null
    )
    {
        if (!is_null($restoreRoute)) 
            session()->flash(JToastEnum::RESTORE, $restoreRoute);

        return session()->flash(JToastEnum::SUCCESS, $message);
    }

    // DANGER
    public function toastError(string $message = 'Something went wrong. Please try again.')
    {
        return session()->flash(JToastEnum::DANGER, $message);
    }

    public function handleException($exception)
    {
        if ($exception instanceof QueryException) 
            return $this->toastError();
        elseif ($exception instanceof ModelNotFoundException) 
            return $this->notfound();
            
        return $this->toastError();
    }

    private function notfound()
    {
        return Inertia::location(route('errors.404'));
    }
}
