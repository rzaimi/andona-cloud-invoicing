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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Truncate icon values that are longer than 10 characters before shrinking column
        // This prevents data truncation errors when rolling back
        $categories = \DB::table('categories')
            ->whereNotNull('icon')
            ->whereRaw('CHAR_LENGTH(icon) > 10')
            ->get();
        
        foreach ($categories as $category) {
            \DB::table('categories')
                ->where('id', $category->id)
                ->update(['icon' => substr($category->icon, 0, 10)]);
        }
        
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 10)->nullable()->change();
        });
    }
};
