<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garage rental transactions. The tenant is found through the vehicle (vehicle_id -> vehicles.user_id),
     * so there is no user_id column here (avoids a transitive dependency / 3NF).
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            // RESTRICT: rental history must be kept, so the vehicle/garage cannot be deleted.
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('garage_id')->constrained('garages')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('rental_type', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedInteger('daily_price');      // snapshot of the daily price at rental time
            $table->unsignedInteger('rent_cost');
            $table->unsignedInteger('charging_cost')->default(0);
            $table->unsignedInteger('total_cost');
            $table->string('payment_proof_path');
            $table->timestamps();

            $table->index(['garage_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
