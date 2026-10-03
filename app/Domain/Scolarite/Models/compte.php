<?php

namespace App\Domain\Scolarite\Models;

use App\Domain\Scolarite\Types\StatutCompte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compte portail parent.
 * Table : comptes
 */
class Compte extends Model
{
    protected $table = 'comptes';

    protected $fillable = [
        'email', 'mot_passe', 'statut_compte',
        'espace_id', 'parent_id', 'password_reset', 'etat',
    ];

    protected $casts = [
        'espace_id'      => 'integer',
        'parent_id'      => 'integer',
        'password_reset' => 'integer',
        'etat'           => 'integer',
        'statut_compte'  => StatutCompte::class,
    ];

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class, 'espace_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentEleve::class, 'parent_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}