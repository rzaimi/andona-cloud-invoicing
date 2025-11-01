<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Deutschland');
            $table->string('tax_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('commercial_register')->nullable();
            $table->string('managing_director')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_iban')->nullable();
            $table->string('bank_bic')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
