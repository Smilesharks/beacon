<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Trigger Delay
    |--------------------------------------------------------------------------
    | Seconds to wait after page load before showing a notification.
    | Prevents Beacon from competing with cookie consent banners.
    | Can be overridden per-notification in the CP.
    */
    'default_delay' => 2,

    /*
    |--------------------------------------------------------------------------
    | Default Display Position
    |--------------------------------------------------------------------------
    | Fallback position when none is specified. Valid values:
    | top-bar, bottom-bar, bottom-right, bottom-left, modal-center
    */
    'default_position' => 'bottom-right',

    /*
    |--------------------------------------------------------------------------
    | Dev Mode Logging
    |--------------------------------------------------------------------------
    | When true, logs a debug-level warning to Statamic's log when multiple
    | targeting rules match the same page. Only relevant in non-production.
    */
    'dev_mode_logging' => env('BEACON_DEV_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | License Key
    |--------------------------------------------------------------------------
    | Your Beacon license key from the Statamic Marketplace.
    */
    'license_key' => env('BEACON_LICENSE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Resolver Cache TTL
    |--------------------------------------------------------------------------
    | Seconds to cache the notification resolver result per entry.
    | Set to 0 to disable caching.
    */
    'cache_ttl' => env('BEACON_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    | Absolute path to the directory where Beacon stores its JSON data files
    | (notifications.json, collection-rules.json). Defaults to
    | storage/app/beacon/ in the host application.
    */
    'storage_path' => storage_path('app/beacon'),

];
