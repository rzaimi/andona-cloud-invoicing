<?php

namespace Tests\Unit\Support;

use App\Support\RebaseCachedStoragePaths;
use Tests\TestCase;

class RebaseCachedStoragePathsTest extends TestCase
{
    public function test_it_replaces_baked_log_paths_with_the_live_storage_path(): void
    {
        config([
            'logging.channels.daily.path' => '/var/www/vhosts/host.example/src/storage/logs/laravel.log',
            'logging.channels.single.path' => '/var/www/vhosts/host.example/src/storage/logs/laravel.log',
            'logging.channels.emergency.path' => '/var/www/vhosts/host.example/src/storage/logs/laravel.log',
        ]);

        RebaseCachedStoragePaths::apply();

        $expected = storage_path('logs/laravel.log');

        $this->assertSame($expected, config('logging.channels.daily.path'));
        $this->assertSame($expected, config('logging.channels.single.path'));
        $this->assertSame($expected, config('logging.channels.emergency.path'));
    }
}
