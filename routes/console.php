<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
