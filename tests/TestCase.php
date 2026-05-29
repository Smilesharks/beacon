<?php

namespace Smilesharks\Beacon\Tests;

use Smilesharks\Beacon\ServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Statamic\Providers\StatamicServiceProvider;
use Statamic\Statamic;

abstract class TestCase extends OrchestraTestCase
{
    protected string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/beacon-test-'.uniqid();
        File::makeDirectory($this->tmpDir, 0755, true, true);
        config(['beacon.storage_path' => $this->tmpDir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmpDir);
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            StatamicServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Statamic' => Statamic::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('beacon.dev_mode_logging', false);
        $app['config']->set('beacon.cache_ttl', 0);
    }
}
