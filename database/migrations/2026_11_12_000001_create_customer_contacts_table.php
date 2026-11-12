<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->string('name');
            // Required by validation; nullable so backfilled contacts of
            // customers without an address stay valid.
            $table->string('email')->nullable();
            $table->string('title')->nullable();
            $table->string('phone', 50)->nullable();
            // The Ansprechpartner printed on documents (synced to customers.contact_person).
            $table->boolean('is_primary')->default(false);
            // Default recipient per purpose for manual and automatic emails.
            $table->boolean('receives_invoices')->default(false);
            $table->boolean('receives_reminders')->default(false);
            $table->boolean('receives_offers')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'sort_order']);
        });

        // Existing free-text Ansprechpartner become the primary contact. No
        // purpose flags are set, so automatic emails keep going to the
        // customer's main address until the user opts in.
        $now = now();
        DB::table('customers')
            ->whereNotNull('contact_person')
            ->where('contact_person', '!=', '')
            ->orderBy('id')
            ->chunk(200, function ($customers) use ($now) {
                $rows = [];
                foreach ($customers as $customer) {
                    $rows[] = [
                        'id' => (string) Str::uuid(),
                        'company_id' => $customer->company_id,
                        'customer_id' => $customer->id,
                        'name' => $customer->contact_person,
                        'email' => $customer->email ?: null,
                        'is_primary' => true,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('customer_contacts')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contacts');
    }
};
