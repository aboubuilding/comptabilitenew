<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Zone de ramassage. Elle appartient à une ligne, qui fournit le chauffeur et
 * le véhicule. Son tarif mensuel est indicatif : l'abonnement peut le dépasser
 * ou rester en dessous.
 */
class Zone extends Model
{
    protected $table = 'zones';

    protected $fillable = [
        'libelle', 'description', 'tarif_base', 'ligne_id', 'chauffeur_id', 'voiture_id',
        'code', 'ordre', 'couleur', 'annee_id', 'etat',
    ];

    protected $casts = [
        'tarif_base' => 'float',
    ];

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(Ligne::class, 'ligne_id');
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class, 'chauffeur_id');
    }

    public function voiture(): BelongsTo
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(AbonnementBus::class, 'zone_id');
    }
}
