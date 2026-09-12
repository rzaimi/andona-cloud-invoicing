<?php

namespace App\Support;

/**
 * Plesk SSH is chrooted (/andobill.de/src) while web/cron often see the
 * real vhost path. `config:cache` bakes whichever path ran artisan, then
 * Monolog dies with "directory ... could not be created: Permission denied".
 * Re-apply live storage_path() after config is loaded.
 */
class RebaseCachedStoragePaths
{
    public static function apply(): void
    {
        config([
            'logging.channels.single.path' => storage_path('logs/laravel.log'),
            'logging.channels.daily.path' => storage_path('logs/laravel.log'),
            'logging.channels.emergency.path' => storage_path('logs/laravel.log'),
        ]);
    }
}
