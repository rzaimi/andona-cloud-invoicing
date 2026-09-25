<?php

namespace App\Support;

/**
 * Plesk SSH is chrooted (/andobill.de/src) while web/cron often see the
 * real vhost path. `config:cache` bakes whichever path ran artisan.
 * Queue workers then cannot write logs, Blade looks for views under a
 * path that does not exist inside the chroot, and the public disk tries
 * to create storage/app/public on the full vhost path.
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
            'filesystems.disks.local.root' => storage_path('app/private'),
            'filesystems.disks.private.root' => storage_path('app/private/tenants'),
            'filesystems.disks.documents.root' => storage_path('app/documents'),
            'filesystems.disks.expenses.root' => storage_path('app/expenses'),
            'filesystems.disks.public.root' => storage_path('app/public'),
            'filesystems.links' => [
                public_path('storage') => storage_path('app/public'),
            ],
        ]);
    }
}
