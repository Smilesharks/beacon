<?php

namespace Smilesharks\Beacon;

use Smilesharks\Beacon\Fieldtypes\BeaconNotificationFieldtype;
use Smilesharks\Beacon\Http\Middleware\BeaconTagPresenceMiddleware;
use Smilesharks\Beacon\Models\BeaconNotification;
use Smilesharks\Beacon\Observers\BeaconNotificationObserver;
use Smilesharks\Beacon\Services\NotificationResolver;
use Smilesharks\Beacon\Tags\BeaconAssetsTag;
use Illuminate\Support\Facades\File;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        BeaconAssetsTag::class,
    ];

    protected $fieldtypes = [
        BeaconNotificationFieldtype::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            BeaconTagPresenceMiddleware::class,
        ],
    ];

    protected $vite = [
        'hotFile' => __DIR__.'/../public/vendor/beacon/hot',
        'publicDirectory' => 'public/vendor/beacon',
        'input' => [
            'resources/js/cp.js',
        ],
    ];

    protected $scripts = [
        __DIR__.'/../public/vendor/beacon/cp.js',
    ];

    public function bootAddon(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/beacon.php', 'beacon');

        $this->publishes([
            __DIR__.'/../config/beacon.php' => config_path('beacon.php'),
        ], 'beacon-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/beacon'),
        ], 'beacon-views');

        $this->publishes([
            __DIR__.'/../public/vendor/beacon' => public_path('vendor/beacon'),
        ], 'beacon-assets');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'beacon');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'beacon');

        $this->loadRoutesFrom(__DIR__.'/../routes/cp.php');

        $this->registerPermissions();

        $this->loadPersistedSettings();

        BeaconNotification::observe(BeaconNotificationObserver::class);

        $this->registerNavigation();
    }

    protected function loadPersistedSettings(): void
    {
        $path = storage_path('app/beacon-settings.json');

        if (! File::exists($path)) {
            return;
        }

        $settings = json_decode(File::get($path), true);

        if (! is_array($settings)) {
            return;
        }

        config(array_combine(
            array_map(fn ($k) => "beacon.{$k}", array_keys($settings)),
            array_values($settings)
        ));
    }

    protected function registerNavigation(): void
    {
        Nav::extend(function ($nav) {
            $nav->tools(__('beacon::nav.section_title'))
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6zM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3z"/></svg>')
                ->route('beacon.dashboard')
                ->can('view beacon')
                ->children([
                    $nav->item(__('beacon::nav.dashboard'))->route('beacon.dashboard'),
                    $nav->item(__('beacon::nav.sitewide'))->route('beacon.sitewide.index'),
                    $nav->item(__('beacon::nav.collections'))->route('beacon.collections.index'),
                    $nav->item(__('beacon::nav.settings'))->route('beacon.settings')->can('manage beacon settings'),
                ]);
        });
    }

    protected function registerPermissions(): void
    {
        Permission::extend(function () {
            Permission::register('view beacon', function ($permission) {
                $permission->label(__('beacon::permissions.view'));
            });

            Permission::register('edit beacon', function ($permission) {
                $permission->label(__('beacon::permissions.edit'));
            });

            Permission::register('manage beacon settings', function ($permission) {
                $permission->label(__('beacon::permissions.manage_settings'));
            });
        });
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(NotificationResolver::class);
    }
}
