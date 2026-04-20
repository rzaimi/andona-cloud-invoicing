<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoice_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');

            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->uuid('layout_id')->nullable();
            // No FK constraint — invoice_layouts is allowed to be deleted; we
            // fall back to the company default layout at generation time.

            // Identity
            $table->string('name');
            $table->text('description')->nullable();

            // Invoice defaults (copied verbatim onto every generated invoice)
            $table->string('vat_regime', 40)->default('standard');
            $table->decimal('tax_rate', 5, 4)->default(0.19);
            $table->string('payment_method')->nullable();
            $table->text('payment_terms')->nullable();
            $table->decimal('skonto_percent', 5, 2)->nullable();
            $table->unsignedInteger('skonto_days')->nullable();
            $table->unsignedInteger('due_days_after_issue')->default(14);
            $table->text('notes')->nullable();
            $table->string('bauvorhaben', 255)->nullable();
            $table->string('auftragsnummer', 100)->nullable();

            // Schedule
            $table->enum('interval_unit', ['day', 'week', 'month', 'quarter', 'year'])->default('month');
            $table->unsignedInteger('interval_count')->default(1);
            // 1..31 — only honoured for month/quarter/year. Day 31 auto-clamps
            // to the last day of shorter months (handled in the service).
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('occurrences_count')->default(0);
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();

            // Lifecycle
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->date('paused_until')->nullable();

            // Auto-send
            $table->boolean('auto_send')->default(false);
            $table->string('email_subject_template')->nullable();
            $table->text('email_body_template')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Generator query: WHERE company_id = ? AND status = 'active'
            // AND next_run_date <= today
            $table->index(['company_id', 'status', 'next_run_date'], 'rip_scan_idx');
            $table->index(['company_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoice_profiles');
    }
};
