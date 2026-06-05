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
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('email');
            $table->string('grade')->nullable()->after('position');
            $table->string('telephone_num')->nullable()->after('grade');
            $table->string('office_num')->nullable()->after('telephone_num');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['position', 'grade', 'telephone_num', 'office_num']);
        });
    }
};
