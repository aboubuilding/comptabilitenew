<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipationEvenement extends Model
{
    use HasFactory;

    protected $table = 'participation_evenements';

    protected $fillable = [
        'evenement_scolaire_id', 'inscripion_id', 'date_inscription',
        'montant_facture', 'statut', 'etat',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'montant_facture'  => 'float',
        'statut'           => 'integer',
        'etat'             => 'integer',
    ];

    public function evenement()
    {
        return $this->belongsTo(Evenement::class, 'evenement_scolaire_id');
    }

    /**
     * ATTENTION : la colonne s'appelle bien 'inscripion_id' (faute de
     * frappe dans la migration d'origine, sans le 't'), pas
     * 'inscription_id'. Reproduite ici à l'identique pour matcher le
     * schéma réel plutôt que de la corriger silencieusement.
     */
    public function inscription()
    {
        return $this->belongsTo(Inscription::class, 'inscripion_id');
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}