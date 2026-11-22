<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rental extension history entered by admins.
     */
    public function up(): void
    {
        Schema::create('rental_extensions', function (Blueprint $table) {
            $table->id();
            // CASCADE: composition, an extension has no meaning without its parent rental.
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete()->cascadeOnUpdate();
            // RESTRICT: audit trail of the admin who processed it.
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->date('old_end_date');
            $table->date('new_end_date');
            $table->unsignedSmallInteger('added_days');
            $table->unsignedInteger('amount_due');
            $table->string('payment_proof_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_extensions');
    }
};
