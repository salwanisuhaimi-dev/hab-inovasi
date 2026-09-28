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
            //
            $table->string('admin_submission_slug')->nullable()->after('submission_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_types', function (Blueprint $table) {
            //
            $table->dropColumn('admin_submission_slug');
        });
    }
};
