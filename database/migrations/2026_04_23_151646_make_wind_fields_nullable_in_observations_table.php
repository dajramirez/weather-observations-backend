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
        Schema::table('observations', function (Blueprint $table) {
            $table->decimal('wind_speed', 5, 2)->nullable()->change();
            $table->integer('wind_direction')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->decimal('wind_speed', 5, 2)->nullable(false)->change();
            $table->integer('wind_direction')->nullable(false)->change();
        });
    }
};
