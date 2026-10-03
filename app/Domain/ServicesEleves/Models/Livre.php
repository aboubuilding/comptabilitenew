<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\StatutPret;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Livre extends Model
{
    protected $table = 'livres';

    protected $fillable = [
        'titre', 'numero', 'numero_isbn',
        'auteur_id', 'maison_edition_id', 'annee_edition_id', 'categorie_livre_id',
        'nombre_exemplaires', 'nombre_disponibles',
        'description', 'photo',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'nombre_exemplaires' => 'integer',
        'nombre_disponibles' => 'integer',
    ];

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(Auteur::class, 'auteur_id');
    }

    public function maisonEdition(): BelongsTo
    {
        return $this->belongsTo(MaisonEdition::class, 'maison_edition_id');
    }

    public function anneeEdition(): BelongsTo
    {
        return $this->belongsTo(AnneEdition::class, 'annee_edition_id');
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieLivre::class, 'categorie_livre_id');
    }

    public function prets(): HasMany
    {
        return $this->hasMany(Pret::class, 'livre_id');
    }

    /** Prêt actuellement en cours pour ce livre (un seul à la fois). */
    public function pretEnCours(): ?Pret
    {
        return $this->prets()
            ->whereIn('statut_pret', [StatutPret::EN_COURS->value, StatutPret::RETARD->value])
            ->orderByDesc('date_pret')
            ->first();
    }

    public function getExemplairesDisponiblesAttribute(): int
    {
        return (int) ($this->nombre_disponibles ?? 0);
    }

    public function getExemplairesTotalAttribute(): int
    {
        return (int) ($this->nombre_exemplaires ?? 1);
    }

    public function getEstDisponibleAttribute(): bool
    {
        return $this->exemplaires_disponibles > 0;
    }
}