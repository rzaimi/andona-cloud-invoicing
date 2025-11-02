<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix model_has_permissions table
        // Drop primary key (it includes model_id)
        DB::statement('ALTER TABLE `model_has_permissions` DROP PRIMARY KEY');
        
        // Drop index that includes model_id
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('model_has_permissions_model_id_model_type_index');
        });

        // Change model_id column type from BIGINT to CHAR(36) for UUID
        DB::statement('ALTER TABLE `model_has_permissions` MODIFY `model_id` CHAR(36) NOT NULL');

        // Recreate primary key and index
        DB::statement('ALTER TABLE `model_has_permissions` ADD PRIMARY KEY (`permission_id`, `model_id`, `model_type`)');
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
        });

        // Fix model_has_roles table
        // Drop primary key (it includes model_id)
        DB::statement('ALTER TABLE `model_has_roles` DROP PRIMARY KEY');
        
        // Drop index that includes model_id
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('model_has_roles_model_id_model_type_index');
        });

        // Change model_id column type from BIGINT to CHAR(36) for UUID
        DB::statement('ALTER TABLE `model_has_roles` MODIFY `model_id` CHAR(36) NOT NULL');

        // Recreate primary key and index
        DB::statement('ALTER TABLE `model_has_roles` ADD PRIMARY KEY (`role_id`, `model_id`, `model_type`)');
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert model_has_permissions table
        DB::statement('ALTER TABLE `model_has_permissions` DROP PRIMARY KEY');
        
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('model_has_permissions_model_id_model_type_index');
        });

        DB::statement('ALTER TABLE `model_has_permissions` MODIFY `model_id` BIGINT UNSIGNED NOT NULL');

        DB::statement('ALTER TABLE `model_has_permissions` ADD PRIMARY KEY (`permission_id`, `model_id`, `model_type`)');
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
        });

        // Revert model_has_roles table
        DB::statement('ALTER TABLE `model_has_roles` DROP PRIMARY KEY');
        
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('model_has_roles_model_id_model_type_index');
        });

        DB::statement('ALTER TABLE `model_has_roles` MODIFY `model_id` BIGINT UNSIGNED NOT NULL');

        DB::statement('ALTER TABLE `model_has_roles` ADD PRIMARY KEY (`role_id`, `model_id`, `model_type`)');
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
        });
    }
};
