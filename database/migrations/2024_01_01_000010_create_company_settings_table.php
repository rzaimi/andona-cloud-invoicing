<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('key');
            $table->text('value');
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'key']);
            $table->index(['company_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_settings');
    }
};
