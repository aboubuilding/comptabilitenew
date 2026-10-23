<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Parcours desservi par un chauffeur et un véhicule pendant une période.
 * Les zones de ramassage lui appartiennent ; les tarifs sont portés par les
 * zones (à titre indicatif), plus par la ligne.
 */
class Ligne extends Model
{
    protected $table = 'lignes';

    protected $fillable = [
        'libelle', 'chauffeur_id', 'voiture_id', 'date_debut', 'date_fin', 'annee_id', 'etat',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class, 'chauffeur_id');
    }

    public function voiture(): BelongsTo
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'ligne_id')->where('etat', 1);
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(AbonnementBus::class, 'ligne_id');
    }
}
