<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compte extends Model
{
    use HasFactory;

    protected $table = 'comptes';

    protected $fillable = ['email', 'mot_passe', 'statut_compte', 'espace_id', 'parent_id', 'etat'];

    protected $hidden = ['mot_passe'];

    protected $casts = ['etat' => 'integer'];

    public function espace()
    {
        return $this->belongsTo(Espace::class, 'espace_id');
    }

    public function parentEleve()
    {
        return $this->belongsTo(ParentEleve::class, 'parent_id');
    }

    /**
     * 'statut_compte' est une simple colonne string en base (pas de liste
     * de valeurs imposée par le schéma) — l'app impose "Actif"/"Inactif"
     * à la saisie, comparaison insensible à la casse en lecture.
     */
    public function isActif(): bool
    {
        return strtolower((string) $this->statut_compte) === 'actif';
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}