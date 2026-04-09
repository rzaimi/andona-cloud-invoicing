<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MySQL : ALTER TABLE to extend the real ENUM.
     * SQLite: recreate the documents table with 'payroll' in the CHECK constraint
     *         (SQLite does not support ALTER COLUMN or DROP CONSTRAINT).
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE documents MODIFY COLUMN category
                 ENUM('payroll','employee','customer','invoice','company','financial','custom')
                 NOT NULL DEFAULT 'custom'"
            );
            return;
        }

        if ($driver === 'sqlite') {
            $this->recreateSqliteTable(
                "'payroll','employee','customer','invoice','company','financial','custom'"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE documents MODIFY COLUMN category
                 ENUM('employee','customer','invoice','company','financial','custom')
                 NOT NULL DEFAULT 'custom'"
            );
            return;
        }

        if ($driver === 'sqlite') {
            $this->recreateSqliteTable(
                "'employee','customer','invoice','company','financial','custom'"
            );
        }
    }

    /**
     * SQLite does not support modifying CHECK constraints in-place.
     * We create a new table with the updated constraint, copy all rows,
     * drop the old table, then rename.
     */
    private function recreateSqliteTable(string $allowedValues): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement("
            CREATE TABLE documents_new (
                id TEXT NOT NULL PRIMARY KEY,
                company_id TEXT NOT NULL,
                name TEXT NOT NULL,
                original_filename TEXT NOT NULL,
                file_path TEXT NOT NULL,
                file_size INTEGER NOT NULL,
                mime_type TEXT NOT NULL,
                category TEXT NOT NULL DEFAULT 'custom'
                    CHECK (category IN ({$allowedValues})),
                description TEXT,
                tags TEXT,
                uploaded_by TEXT,
                linkable_type TEXT,
                linkable_id TEXT,
                link_type TEXT,
                created_at TEXT,
                updated_at TEXT,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        DB::statement('INSERT INTO documents_new SELECT * FROM documents');
        DB::statement('DROP TABLE documents');
        DB::statement('ALTER TABLE documents_new RENAME TO documents');

        // Restore indexes
        DB::statement('CREATE INDEX IF NOT EXISTS documents_company_id_index ON documents (company_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_category_index ON documents (category)');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_linkable_type_linkable_id_index ON documents (linkable_type, linkable_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_uploaded_by_index ON documents (uploaded_by)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
