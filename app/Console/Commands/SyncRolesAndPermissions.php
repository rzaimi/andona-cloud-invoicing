<?php

namespace App\Console\Commands;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;

class SyncRolesAndPermissions extends Command
{
    protected $signature = 'roles:sync';

    protected $description = 'Ensure Spatie roles and permissions exist (idempotent). Run on production if role checkboxes are missing in user edit.';

    public function handle(): int
    {
        $this->info('Running RolesAndPermissionsSeeder…');
        $this->call(RolesAndPermissionsSeeder::class);

        return self::SUCCESS;
    }
}
