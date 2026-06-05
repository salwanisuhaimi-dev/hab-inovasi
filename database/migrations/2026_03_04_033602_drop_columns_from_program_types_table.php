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
        Schema::table('program_types', function (Blueprint $table) {
            $table->dropColumn(['slug', 'category', 'level', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_types', function (Blueprint $table) {
            $table->string('slug')->nullable();
            $table->year('category')->nullable();
            $table->string('level')->nullable();
            $table->string('description')->nullable();
        });
    }
};
