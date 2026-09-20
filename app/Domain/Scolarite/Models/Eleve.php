<?php

namespace App\Domain\Scolarite\Models;

use App\Domain\Finances\Models\Detail;
use App\Domain\Scolarite\Types\LienParentePersonne;
use App\Domain\Scolarite\Types\Sexe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eleve extends Model
{
    use HasFactory;

    protected $table = 'eleves';

    protected $fillable = [
        'matricule', 'nom', 'prenom', 'prenom_usuel', 'ecole_provenance',
        'date_naissance', 'lieu_naissance', 'sexe', 'nationalite_id', 'espace_id',
        'nom_medecin', 'personne_prevenir', 'photo', 'carte_identite', 'naissance',
        'groupe_id', 'certificat_medical',
        'vaccin_1', 'vaccin_2', 'vaccin_3', 'vaccin_4', 'vaccin_5',
        'numero_medecin', 'numero_personne_prevenir', 'lien_parente_personne',
        'naissance_eleve', 'allergie', 'etat',
    ];

    protected $casts = [
        'date_naissance'        => 'date',
        'sexe'                  => Sexe::class,
        'lien_parente_personne' => LienParentePersonne::class,
        'etat'                  => 'integer',
    ];

    public const ETAT_ACTIF   = 1;
    public const ETAT_INACTIF = 0;

    public function nationalite()
    {
        return $this->belongsTo(Nationalite::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Inscription pour une année donnée — c'est elle qui porte
     * classe/cycle/niveau/statut d'abandon, pas la fiche élève
     * elle-même (cloisonnement par année scolaire).
     */
    public function inscriptionPourAnnee()
    {
        return $this->hasOne(Inscription::class)->latestOfMany();
    }

    /**
     * Lecture cross-domaine en fin de chaîne (Scolarite -> Finances),
     * en lecture seule pour une agrégation — voir la note sur le modèle
     * Detail. A remplacer par un service Finances/Recouvrement dédié.
     */
    public function details()
    {
        return $this->hasManyThrough(Detail::class, Inscription::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->prenom_usuel ?: $this->prenom) . ' ' . $this->nom);
    }

    public function isActive(): bool
    {
        return (int) $this->etat === self::ETAT_ACTIF;
    }

    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }
}