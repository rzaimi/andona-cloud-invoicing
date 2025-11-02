<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Create a unique partial index to ensure only one default warehouse per company
        // This is PostgreSQL syntax - for MySQL, we'll handle this in the model
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX warehouses_company_default_unique ON warehouses (company_id) WHERE is_default = true');
        }
    }

    public function down()
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS warehouses_company_default_unique');
        }
    }
};
