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
        Schema::table('program_agendas', function (Blueprint $table) {
          $table->foreignId('speaker_id')
                ->nullable()
                ->after('title')
                ->constrained('speakers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_agendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('speaker_id');
        });
    }
};
