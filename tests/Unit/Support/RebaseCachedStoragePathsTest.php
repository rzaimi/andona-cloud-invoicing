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

    public function test_it_replaces_baked_view_paths_so_invoice_pdf_resolves(): void
    {
        config([
            'view.paths' => ['/var/www/vhosts/host.example/src/resources/views'],
            'view.compiled' => '/var/www/vhosts/host.example/src/storage/framework/views',
        ]);

        RebaseCachedStoragePaths::apply();

        $this->app->forgetInstance('view');
        $this->app->forgetInstance('blade.compiler');

        $path = $this->app->make('view.finder')->find('pdf.invoice');

        $this->assertSame(resource_path('views/pdf/invoice.blade.php'), $path);
        $this->assertSame(storage_path('framework/views'), config('view.compiled'));
    }
}
