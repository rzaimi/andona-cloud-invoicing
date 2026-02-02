<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AddStornorechnungPermissionSeeder::class, // GoBD compliance permission
            CompanySeeder::class,
            CompanySettingsSeeder::class,
            UserSeeder::class,
            CustomerSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            InvoiceSeeder::class, // Now includes audit log entries
            OfferSeeder::class,
            ExpenseCategorySeeder::class,
            ExpenseSeeder::class,
            PaymentSeeder::class,
            CalendarEventSeeder::class,
        ]);
    }
}
