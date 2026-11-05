<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Latest position cache
    |--------------------------------------------------------------------------
    */
    'position_ttl_seconds' => 600,

    /** Owner, rescuer and closed state. Report saves clear this straight away. */
    'report_cache_seconds' => 15,

    /*
    |--------------------------------------------------------------------------
    | Stored path thinning
    |--------------------------------------------------------------------------
    |
    | A sample gets a report_location_points row only when it is at least this
    | far, or this long, from the last row stored for that user and report.
    |
    */
    'store_min_distance_meters' => 50,
    'store_min_seconds' => 30,

    /** Older samples are dropped: they are too late to be useful. */
    'max_sample_age_seconds' => 3600,

    /** Delete stored path points older than this (see live-location:prune). */
    'retention_days' => (int) env('LIVE_LOCATION_RETENTION_DAYS', 30),

    /** Newest points per person drawn on the web map. */
    'map_path_limit' => 2000,

    /*
    |--------------------------------------------------------------------------
    | Admin Live Map
    |--------------------------------------------------------------------------
    |
    | Open reports older than this leave the map unless a rescue is under way,
    | so years of never-closed reports don't bury today's incidents.
    |
    */
    'overview_recent_days' => 7,
    'overview_report_limit' => 200,

    /*
    |--------------------------------------------------------------------------
    | Traffic limits
    |--------------------------------------------------------------------------
    */
    'broadcast_min_seconds' => 2,
    'rate_limit_per_minute' => 60,
    'retry_after_seconds' => 30,

    /*
    |--------------------------------------------------------------------------
    | next_interval_seconds
    |--------------------------------------------------------------------------
    |
    | Tiers apply once total live-location requests per minute reach the
    | threshold. Navigating stays the fastest mode, capped by the max values.
    | Starting thresholds; tune them against real load on the production server.
    |
    */
    'interval_seconds' => 4,
    'max_interval_seconds' => ['navigating' => 10, 'other' => 60],
    'load_tiers' => [
        ['requests_per_minute' => 600, 'navigating' => 6, 'other' => 15],
        ['requests_per_minute' => 1200, 'navigating' => 8, 'other' => 30],
        ['requests_per_minute' => 2400, 'navigating' => 10, 'other' => 60],
    ],
];
