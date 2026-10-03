<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parent / tuteur rattaché à un espace familial.
 */
class ParentEleve extends Model
{
    protected $table = 'parent_eleves';

    protected $fillable = [
        'nom_parent', 'prenom_parent', 'telephone', 'profession',
        'espace_id', 'is_principal', 'role', 'annee_id', 'nationalite_id',
        'whatsapp', 'quartier', 'adresse', 'email', 'etat',
    ];

    protected $casts = [
        'is_principal'   => 'integer',
        'role'           => 'integer',
        'annee_id'       => 'integer',
        'nationalite_id' => 'integer',
        'etat'           => 'integer',
    ];

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class, 'espace_id');
    }

    public function getNomCompletAttribute(): string
    {
        return trim(($this->nom_parent ?? '') . ' ' . ($this->prenom_parent ?? ''));
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}