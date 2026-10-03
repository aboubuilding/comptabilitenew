<?php

namespace App\Domain\Scolarite\Models;

use App\Domain\Scolarite\Types\StatutAnnee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Annee extends Model
{
    use HasFactory;

    protected $table = 'annees';

    protected $fillable = [
        'libelle',
        'date_rentree',
        'date_fin',
        'date_ouverture_inscription',
        'date_fermeture_reinscription',
        'statut_annee',
        'etat',
    ];

    protected $casts = [
        'date_rentree'                 => 'date:Y-m-d',
        'date_fin'                     => 'date:Y-m-d',
        'date_ouverture_inscription'   => 'date:Y-m-d',
        'date_fermeture_reinscription' => 'date:Y-m-d',
        'statut_annee'                 => StatutAnnee::class,
        'etat'                         => 'integer',
    ];

    /**
     * Alignées sur App\Repositories\BaseRepository (ACTIF = 1 / INACTIF =
     * 2).
     */
    public const ETAT_ACTIF   = 1;
    public const ETAT_INACTIF = 2;

    public function isOuverte(): bool
    {
        return $this->statut_annee === StatutAnnee::Ouvert;
    }

    public function isCloturee(): bool
    {
        return $this->statut_annee === StatutAnnee::Cloture;
    }

    /** Année créée mais pas encore ouverte (ou sans statut dans la base historique). */
    public function isNonOuverte(): bool
    {
        return ! $this->isOuverte() && ! $this->isCloturee();
    }

    public function getStatutLabelAttribute(): ?string
    {
        return $this->statut_annee?->label();
    }

    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    public function scopeOuvertes($query)
    {
        return $query->where('statut_annee', StatutAnnee::Ouvert->value);
    }

    /*
     * Pas de relations (classes(), inscriptions(), paiements()...) pour
     * l'instant : ces modèles n'existent pas encore côté Domain\Scolarite
     * et Domain\Finances. A ajouter au fur et à mesure de leur
     * construction, plutôt que de les déclarer à l'avance sans modèle en
     * face.
     */
}