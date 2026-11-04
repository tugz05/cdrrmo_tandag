<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/** Real-time incident alerts for the admin dashboard (see App\Events\MessageReportReceived). */
Broadcast::channel('admin.reports', function ($user) {
    return $user->hasRole(['admin', 'super_admin']);
});
