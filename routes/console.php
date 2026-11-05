<?php

use App\Models\ReportLocationPoint;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('valid-ids:secure', function () {
    $public = Storage::disk('public');
    $private = Storage::disk('local');
    $moved = 0;

    foreach ($public->files('img_valid_ids') as $path) {
        if (! $private->exists($path)) {
            $private->put($path, $public->get($path));
        }

        // Keep the public copy unless the private one is confirmed complete.
        if ($private->size($path) !== $public->size($path)) {
            $this->error("Size mismatch, left in place: {$path}");

            continue;
        }

        $public->delete($path);
        $moved++;
    }

    $this->info("Moved {$moved} valid ID image(s) to private storage.");
})->purpose('Move government ID images out of publicly reachable storage');

Artisan::command('live-location:prune', function () {
    // At least one day, so a mistaken 0 can't delete everything.
    $cutoff = now()->subDays(max(1, (int) config('live_location.retention_days')));
    $chunkSize = 1000;
    $deleted = 0;

    do {
        $ids = ReportLocationPoint::query()
            ->where('recorded_at', '<', $cutoff)
            ->orderBy('id')
            ->limit($chunkSize)
            ->pluck('id');

        $deleted += ReportLocationPoint::query()->whereKey($ids)->delete();
    } while ($ids->count() === $chunkSize);

    $this->info("Deleted {$deleted} expired location point(s).");
})->purpose('Delete stored live location paths past the retention period');

Schedule::command('live-location:prune')->daily()->withoutOverlapping();
