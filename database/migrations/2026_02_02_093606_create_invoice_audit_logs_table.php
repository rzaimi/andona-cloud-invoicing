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
        Schema::create('invoice_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('invoice_id');
            $table->uuid('user_id')->nullable();
            $table->string('action', 50); // created, updated, status_changed, sent, paid, cancelled, corrected
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->json('changes')->nullable(); // Detailed field changes
            $table->text('notes')->nullable(); // Audit notes/reason
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes for faster queries
            $table->index('invoice_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_audit_logs');
    }
};
