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
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('original_filename');
            $table->string('file_path'); // Storage path
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('mime_type');
            $table->enum('category', [
                'employee',
                'customer',
                'invoice',
                'company',
                'financial',
                'custom'
            ])->default('custom');
            $table->text('description')->nullable();
            $table->json('tags')->nullable(); // Array of tags
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Polymorphic relationship for linking to entities
            $table->string('linkable_type')->nullable();
            $table->uuid('linkable_id')->nullable();
            $table->string('link_type')->nullable(); // attachment, contract, receipt, etc.
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('category');
            $table->index(['linkable_type', 'linkable_id']);
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
