<?php

namespace App\Policies;

use App\Models\SituationalIncidentReport;
use App\Models\User;

class SituationalIncidentReportPolicy
{
    public function view(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return (int) $user->id === (int) $situationalIncidentReport->user_id;
    }

    public function update(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return (int) $user->id === (int) $situationalIncidentReport->user_id;
    }

    public function delete(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return (int) $user->id === (int) $situationalIncidentReport->user_id;
    }
}
