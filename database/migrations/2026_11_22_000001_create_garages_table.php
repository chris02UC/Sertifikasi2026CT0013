<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garage master data. Codes are generated automatically: S001 (bicycle), C001 (car), E001 (electric car).
     */
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique();
            $table->string('type', 20);
            $table->unsignedInteger('daily_price');             // rupiah per day
            $table->unsignedInteger('kwh_price')->nullable();   // rupiah per kWh, electric-car garages only
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
