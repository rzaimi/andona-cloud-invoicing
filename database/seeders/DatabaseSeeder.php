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
            CategorySeeder::class,
            ProductSeeder::class,
            InvoiceSeeder::class,
            OfferSeeder::class,
            ExpenseCategorySeeder::class,
            ExpenseSeeder::class,
            PaymentSeeder::class,
            CalendarEventSeeder::class,
        ]);
    }
}
