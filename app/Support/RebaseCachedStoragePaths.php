<?php

namespace App\Support;

/**
 * Plesk SSH is chrooted (/andobill.de/src) while web/cron often see the
 * real vhost path. `config:cache` bakes whichever path ran artisan.
 * Queue workers then cannot write logs, and Blade looks for views such
 * as pdf.invoice under a path that does not exist inside the chroot.
 * Re-apply live paths after config is loaded.
 */
class RebaseCachedStoragePaths
{
    public static function apply(): void
    {
        config([
            'logging.channels.single.path' => storage_path('logs/laravel.log'),
            'logging.channels.daily.path' => storage_path('logs/laravel.log'),
            'logging.channels.emergency.path' => storage_path('logs/laravel.log'),
            'view.paths' => [resource_path('views')],
            'view.compiled' => storage_path('framework/views'),
        ]);
    }
}
