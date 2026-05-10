<?php

namespace App\Policies;

use App\Enums\AppMobileRole;
use App\Models\SituationalIncidentReport;
use App\Models\User;

class SituationalIncidentReportPolicy
{
    public function view(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return $this->isOwnerOrStaff($user, $situationalIncidentReport);
    }

    public function update(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return $this->isOwnerOrStaff($user, $situationalIncidentReport);
    }

    public function delete(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        return $this->isOwnerOrStaff($user, $situationalIncidentReport);
    }

    private function isOwnerOrStaff(User $user, SituationalIncidentReport $situationalIncidentReport): bool
    {
        if ($user->mobileApiAppRole() === AppMobileRole::Staff) {
            return true;
        }

        return (int) $user->id === (int) $situationalIncidentReport->user_id;
    }
}
