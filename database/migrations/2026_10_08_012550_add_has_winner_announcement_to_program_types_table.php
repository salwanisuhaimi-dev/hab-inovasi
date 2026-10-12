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
            $table->boolean('has_winner_announcement')->default(false)->after('requires_submission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_types', function (Blueprint $table) {
            $table->dropColumn('has_winner_announcement');
        });
    }
};
