<?php

namespace App\Domain\Administration\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Contact tiers externe.
 */
class ContactTiers extends Model
{
    protected $table = 'contacts_tiers';

    protected $fillable = [
        'nom', 'email', 'telephone', 'organisation', 'etat',
    ];

    protected $casts = [
        'etat' => 'integer',
    ];

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}