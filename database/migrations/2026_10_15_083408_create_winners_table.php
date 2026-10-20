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
        Schema::create('winners', function (Blueprint $table) {
            $table->id();

            // Single FK linking directly to the submission
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();

            // Category/Label (e.g. 'Juara', 'Naib Juara', 'Tempat Ketiga', 'Saguhati')
            $table->string('label');

            // Optional sorting order for podium arrangement (1 for Juara, 2 for Naib Juara, etc.)
            $table->integer('position')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('winners');
    }
};
