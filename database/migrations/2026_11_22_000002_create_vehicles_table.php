<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant vehicles (one tenant can own many vehicles).
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            // RESTRICT: a tenant cannot be deleted while they still own vehicles (master data).
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('type', 20);
            $table->string('plate_number', 15)->nullable();     // empty for bicycles
            $table->string('brand_model', 100);
            $table->decimal('battery_kwh', 8, 2)->nullable();   // electric cars only
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
