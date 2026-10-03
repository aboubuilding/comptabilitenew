<?php

namespace App\Domain\Rh\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employe extends Model
{
    protected $table = 'employes';

    protected $fillable = [
        'nom_prenom', 'numero_cnss', 'quartier', 'ville', 'telephone',
        'date_embauche', 'date_naissance', 'lieu_naissance', 'email',
        'genre', 'situation_familiale', 'date_fin_contrat',
        'is_cnss', 'is_amu', 'nbre_enfant',
        'is_pension_validite', 'taux_pension_validite', 'nature_pension_validite',
        'mode_paiement', 'banque', 'iban', 'titulaire_compte', 'irpp_mode',
        'salaire', 'profession_id', 'annee_id', 'etat',
    ];

    protected $casts = [
        'date_embauche'          => 'date',
        'date_naissance'         => 'date',
        'date_fin_contrat'       => 'date',
        'is_cnss'                => 'integer',
        'is_amu'                 => 'integer',
        'is_pension_validite'    => 'integer',
        'taux_pension_validite'  => 'integer',
        'nature_pension_validite'=> 'float',
        'nbre_enfant'            => 'integer',
        'salaire'                => 'float',
    ];

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class, 'profession_id');
    }

    public function isActif(): bool
    {
        return (int) $this->etat === 1;
    }

    /** Nom formaté : le nom_prenom est déjà dans la base. */
    public function getNomCompletAttribute(): string
    {
        return $this->nom_prenom ?? '—';
    }

    /** Ancienneté en années (0 si pas de date d'embauche). */
    public function getAncienneteAttribute(): int
    {
        if (! $this->date_embauche) return 0;
        return (int) $this->date_embauche->diffInYears(now());
    }

    /** Âge en années (0 si pas de date de naissance). */
    public function getAgeAttribute(): int
    {
        if (! $this->date_naissance) return 0;
        return (int) $this->date_naissance->diffInYears(now());
    }

    public function isAssureCnss(): bool
    {
        return (int) $this->is_cnss === 1;
    }

    public function isAssureAmu(): bool
    {
        return (int) $this->is_amu === 1;
    }
    
    public function isPayeParBanque(): bool
{
    return $this->mode_paiement === 'banque';
}

public function isPayeABilletterie(): bool
{
    return $this->mode_paiement === 'billette';
}

}