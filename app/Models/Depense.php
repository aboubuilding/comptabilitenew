<?php

namespace App\Models;

use App\Types\StatutDepense;
use App\Types\TypeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Depense extends Model
{
    use HasFactory;

    protected $table = 'depenses';

    /**
     * Champs mass-assignables (incluant les nouveaux champs de la migration)
     */
    protected $fillable = [
        'libelle',
        'beneficiaire',        // Corrigé : 'beneficaire' -> 'beneficiaire'
        'motif_depense',
        'date_depense',
        'montant',
        'annee_id',
        'utilisateur_id',
        'statut_depense',
        'etat',
        // 👉 Nouveaux champs ajoutés par migration
        'validateur_id',
        'date_validation',
        'justificatif_demande',
        'motif_rejet', 
    ];

    /**
     * Casting automatique des types
     */
    protected $casts = [
        'montant'         => 'decimal:2',
        'date_depense'    => 'date',
        'date_validation' => 'date',
    ];

    /**
     * Valeurs par défaut (remplace le constructeur)
     */
    protected $attributes = [
        'etat'           => TypeStatus::ACTIF,
        'statut_depense' => StatutDepense::EN_ATTENTE,
    ];

    /**
     * 🔹 Scopes de filtrage (remplacent getListe() & getTotal())
     */
    public function scopeActif($query)
    {
        return $query->where('etat', TypeStatus::ACTIF);
    }

    public function scopeByAnnee($query, ?int $anneeId)
    {
        return $anneeId ? $query->where('annee_id', $anneeId) : $query;
    }

    public function scopeByStatut($query, ?int $statut)
    {
        return $statut !== null ? $query->where('statut_depense', $statut) : $query;
    }

    public function scopeByUtilisateur($query, ?int $userId)
    {
        return $userId ? $query->where('utilisateur_id', $userId) : $query;
    }

    /**
     * 🔗 Relations
     */
    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function caisse(): BelongsTo
{
    return $this->belongsTo(Caisse::class, 'caisse_id');
}

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Mouvement::class, 'depense_id');
    }

    /**
     * 🧮 Attribut calculé : montant formaté pour l'affichage
     */
    protected function montantFormate(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->montant, 2, ',', ' ') . ' ' . config('app.currency', 'FCFA')
        );
    }
}