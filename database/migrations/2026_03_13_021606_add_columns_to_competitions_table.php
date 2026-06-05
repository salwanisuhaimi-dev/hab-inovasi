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
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('id');
            $table->string('description')->nullable()->after('name');
            $table->text('introduction')->nullable()->after('description'); 
            $table->integer('cycle')->default(1)->after('introduction');
        
            $table->json('objectives')->nullable()->after('cycle');
            $table->json('requirements')->nullable()->after('objectives');
            $table->json('prizes')->nullable()->after('requirements');
            $table->json('categories')->nullable()->after('prizes');
            $table->json('tracks')->nullable()->after('categories');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn([
            'slug', 'description', 'introduction', 'cycle', 
            'objectives', 'requirements', 'prizes', 'categories', 'tracks'
            ]);
        });
    }
};
