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
        Schema::create('coffee_break_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffee_break_session_id')->constrained()->cascadeOnDelete();
            $table->string('category'); 
            $table->string('is_digital');
            $table->string('title');
            $table->text('suggestion');
            $table->text('action_taken');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_break_ideas');
    }
};
