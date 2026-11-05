<?php

use App\Models\Report;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/** Real-time incident alerts for the admin dashboard (see App\Events\MessageReportReceived). */
Broadcast::channel('admin.reports', function ($user) {
    return $user->hasRole(['admin', 'super_admin']);
});

/** Live positions and rescue status for one report (see App\Events\LiveLocationUpdated). */
Broadcast::channel('report.{reportId}.tracking', function ($user, $reportId) {
    $report = Report::query()->find($reportId, ['id', 'user_id', 'rescuer_user_id']);

    if (! $report) {
        return false;
    }

    return $user->isVoiceDispatchOperator()
        || (int) $report->user_id === (int) $user->id
        || (int) $report->rescuer_user_id === (int) $user->id;
});

/** On-duty staff positions. */
Broadcast::channel('staff.locations', function ($user) {
    return $user->hasRole(['admin', 'super_admin']);
});

/** Every incident and rescuer position, for the admin Live Map. */
Broadcast::channel('admin.live-map', function ($user) {
    return $user->hasRole(['admin', 'super_admin']);
});
