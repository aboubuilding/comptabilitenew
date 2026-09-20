<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Nommé ParentEleve (comme la table) plutôt que "Parent" : éviter toute
 * confusion avec le mot-clé PHP parent:: dans une classe portant ce nom.
 */
class ParentEleve extends Model
{
    use HasFactory;

    protected $table = 'parent_eleves';

    protected $fillable = [
        'nom_parent', 'prenom_parent', 'telephone', 'whatsapp', 'email', 'profession',
        'espace_id', 'is_principal', 'role', 'annee_id', 'nationalite_id',
        'quartier', 'adresse', 'etat',
    ];

    protected $casts = [
        'etat'         => 'integer',
        'is_principal' => 'boolean',
    ];

    public function espace()
    {
        return $this->belongsTo(Espace::class, 'espace_id');
    }

    public function nationalite()
    {
        return $this->belongsTo(Nationalite::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->prenom_parent . ' ' . $this->nom_parent);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}