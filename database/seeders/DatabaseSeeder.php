<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CompanySeeder::class,
            CompanySettingsSeeder::class,
            UserSeeder::class,
            CustomerSeeder::class,
            InvoiceSeeder::class,
            OfferSeeder::class,
        ]);
    }
}
