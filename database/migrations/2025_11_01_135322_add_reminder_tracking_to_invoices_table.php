<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Reminder escalation level: 0=none, 1=friendly, 2=mahnung_1, 3=mahnung_2, 4=mahnung_3, 5=inkasso
            $table->integer('reminder_level')->default(0)->after('status');
            
            // Last time a reminder was sent
            $table->timestamp('last_reminder_sent_at')->nullable()->after('reminder_level');
            
            // Accumulated reminder fees
            $table->decimal('reminder_fee', 10, 2)->default(0)->after('last_reminder_sent_at');
            
            // Track reminder history as JSON
            $table->json('reminder_history')->nullable()->after('reminder_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('invoices', 'reminder_level')) {
                $columnsToDrop[] = 'reminder_level';
            }
            if (Schema::hasColumn('invoices', 'last_reminder_sent_at')) {
                $columnsToDrop[] = 'last_reminder_sent_at';
            }
            if (Schema::hasColumn('invoices', 'reminder_fee')) {
                $columnsToDrop[] = 'reminder_fee';
            }
            if (Schema::hasColumn('invoices', 'reminder_history')) {
                $columnsToDrop[] = 'reminder_history';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
