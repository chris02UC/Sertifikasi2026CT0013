<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rental extension record entered by an admin.
 *
 * amount_due = remaining amount to pay for the added days (always > 0).
 * Relationships: part of one Rental (composition); processed by one admin (User).
 *
 * @author  Christopher
 *
 * @version 1.0
 */
class RentalExtension extends Model
{
    protected $fillable = [
        'rental_id', 'admin_id', 'old_end_date', 'new_end_date', 'added_days', 'amount_due', 'payment_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'old_end_date' => 'date',
            'new_end_date' => 'date',
            'added_days' => 'integer',
            'amount_due' => 'integer',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
