<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','employee') NOT NULL DEFAULT 'user'"
            );
        } elseif ($driver === 'sqlite') {
            $this->recreateUsersTable("'admin','user','employee'");
        }

        if (!Schema::hasColumn('documents', 'visible_to_employee')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->boolean('visible_to_employee')->default(true)->after('link_type');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        // Demote any 'employee' users to 'user' before removing 'employee' from the constraint.
        DB::statement("UPDATE users SET role = 'user' WHERE role = 'employee'");

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user'"
            );
        } elseif ($driver === 'sqlite') {
            $this->recreateUsersTable("'admin','user'");
        }

        if (Schema::hasColumn('documents', 'visible_to_employee')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('visible_to_employee');
            });
        }
    }

    private function recreateUsersTable(string $allowedRoles): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // Drop any orphaned _new table left by a previously failed migration run
        DB::statement('DROP TABLE IF EXISTS users_new');

        DB::statement("
            CREATE TABLE users_new (
                id TEXT NOT NULL PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                email_verified_at TEXT,
                password TEXT NOT NULL,
                remember_token TEXT,
                created_at TEXT,
                updated_at TEXT,
                company_id TEXT,
                role TEXT NOT NULL DEFAULT 'user'
                    CHECK (role IN ({$allowedRoles})),
                status TEXT NOT NULL DEFAULT 'active'
                    CHECK (status IN ('active','inactive')),
                staff_number TEXT,
                department TEXT,
                job_title TEXT,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
            )
        ");

        DB::statement("
            INSERT INTO users_new
                (id, name, email, email_verified_at, password, remember_token,
                 created_at, updated_at, company_id, role, status)
            SELECT id, name, email, email_verified_at, password, remember_token,
                   created_at, updated_at, company_id, role, status
            FROM users
        ");

        DB::statement('DROP TABLE users');
        DB::statement('ALTER TABLE users_new RENAME TO users');
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
