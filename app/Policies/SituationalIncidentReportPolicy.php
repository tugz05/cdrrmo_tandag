<?php

namespace App\Policies;

use App\Models\SituationalIncidentReport;
use App\Models\User;

class SituationalIncidentReportPolicy
{
    public function view(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return $this->isOwnerOrPrivileged($user, $situationalIncidentReport);
    }

    public function update(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return $this->isOwnerOrPrivileged($user, $situationalIncidentReport);
    }

    public function delete(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return $this->isOwnerOrPrivileged($user, $situationalIncidentReport);
    }

    private function isOwnerOrPrivileged(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        if ($user->mayAccessAllSituationalIncidentReportsViaApi()) {
            return true;
        }

        return (int) $user->id === (int) $situationalIncidentReport->user_id;
    }
}
