<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        return (int) $report->user_id === (int) $user->id || $user->isVoiceDispatchOperator();
    }

    public function update(User $user, Report $report): bool
    {
        return $user->isVoiceDispatchOperator();
    }
}
