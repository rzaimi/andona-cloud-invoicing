<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('title');
            $table->string('type')->default('appointment'); // appointment, invoice_due, offer_expiry, report, inventory
            $table->date('date');
            $table->time('time');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->uuid('related_id')->nullable(); // For linking to invoices, offers, etc.
            $table->string('related_type')->nullable(); // Invoice, Offer, etc.
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly, yearly
            $table->date('recurrence_end_date')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'type']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
