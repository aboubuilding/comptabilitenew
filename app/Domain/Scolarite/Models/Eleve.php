<?php

namespace App\Domain\Scolarite\Models;

use App\Domain\Scolarite\Types\Sexe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modèle élève. Table : eleves
 */
class Eleve extends Model
{
    protected $table = 'eleves';

    protected $fillable = [
        'matricule', 'nom', 'prenom', 'prenom_usuel', 'ecole_provenance',
        'date_naissance', 'lieu_naissance', 'sexe',
        'nationalite_id', 'espace_id',
        'nom_medecin', 'numero_medecin',
        'personne_prevenir', 'numero_personne_prevenir', 'lien_parente_personne',
        'photo', 'carte_identite', 'naissance', 'naissance_eleve', 'certificat_medical',
        'groupe_id', 'vaccin_1', 'vaccin_2', 'vaccin_3', 'vaccin_4', 'vaccin_5',
        'allergie', 'etat',
    ];

    protected $casts = [
        'sexe'                  => Sexe::class,
        'date_naissance'        => 'date',
        'lien_parente_personne' => 'integer',
        'nationalite_id'        => 'integer',
        'espace_id'             => 'integer',
        'groupe_id'             => 'integer',
        'etat'                  => 'integer',
    ];

    protected $appends = ['nom_complet', 'age'];

    public function getNomCompletAttribute(): string
    {
        return trim(($this->nom ?? '') . ' ' . ($this->prenom ?? ''));
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_naissance?->age;
    }

    public function nationalite(): BelongsTo
    {
        return $this->belongsTo(Nationalite::class, 'nationalite_id');
    }

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class, 'espace_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'eleve_id');
    }

    /**
     * Inscription active : validée, la plus récente par annee_id.
     * NB : ne filtre PAS sur l'année active ici — c'est le repository
     * qui applique le filtre selon AnneeScolaireContext.
     */
    public function inscriptionActive(): HasOne
    {
        return $this->hasOne(Inscription::class, 'eleve_id')
            ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value)
            ->latest('annee_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }

    public function scopeRecherche($query, string $terme)
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
              ->orWhere('prenom', 'like', "%{$terme}%")
              ->orWhere('matricule', 'like', "%{$terme}%");
        });
    }
}