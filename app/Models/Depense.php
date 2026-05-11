<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    use HasFactory;

    protected $table = 'depenses';

    protected $fillable = [
        'libelle',
        'beneficiaire',
        'motif_depense',
        'date_depense',
        'montant',
        'annee_id',
        'utilisateur_id',
        'statut_depense',
        'etat',
    ];

    protected $casts = [
        'date_depense' => 'date',
        'montant' => 'integer',
        'statut_depense' => 'integer',
        'etat' => 'integer',
    ];

    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function isActive(): bool
    {
        return $this->etat == 1;
    }

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut_depense) {
            0 => 'En attente',
            1 => 'Validée',
            2 => 'Rejetée',
            default => 'Inconnu',
        };
    }
}
