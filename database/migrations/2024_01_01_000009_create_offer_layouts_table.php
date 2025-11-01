<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('offer_layouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('template')->default('modern');
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['company_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('offer_layouts');
    }
};
