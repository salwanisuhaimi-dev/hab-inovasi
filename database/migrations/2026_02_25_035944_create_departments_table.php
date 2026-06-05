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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama Jabatan (cth: IT, HR, Kewangan)
            $table->string('code')->unique(); // Kod Jabatan (cth: JTM, JKEW)
            $table->text('description')->nullable();
            $table->string('status')->default('aktif'); // Kita guna 'aktif' macam tadi
            $table->timestamps();
        });    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
