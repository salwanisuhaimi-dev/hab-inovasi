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
        Schema::create('coffee_break_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('created_by');
            $table->date('date_created');
            $table->string('location');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('open'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_break_sessions');
    }
};
