<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssuranceVehicule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'assurances_vehicules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'voiture_id',
        'compagnie_assurance',
        'numero_contrat',
        'date_debut',
        'date_fin',
        'prime',
        'type_assurance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'prime'      => 'decimal:2',
    ];

    // ===== CONSTANTES POUR `type_assurance` =====
    const TYPE_TIERS        = 'tiers';
    const TYPE_TOUS_RISQUES = 'tous risques';
    const TYPE_INTERMEDIAIRE = 'intermédiaire';

    // ===== RELATIONS =====
    /**
     * Relation avec la voiture.
     */
    public function voiture()
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le contrat est en cours.
     */
    public function estEnCours(): bool
    {
        $now = now()->startOfDay();
        return $this->date_debut <= $now && $this->date_fin >= $now;
    }

    /**
     * Vérifier si le contrat est expiré.
     */
    public function estExpire(): bool
    {
        return $this->date_fin < now()->startOfDay();
    }

    /**
     * Vérifier si le contrat va expirer dans X jours.
     */
    public function vaExpirerDans(int $jours): bool
    {
        return $this->date_fin->diffInDays(now()->startOfDay()) <= $jours && !$this->estExpire();
    }

    /**
     * Libellé du type d'assurance.
     */
    public function getTypeLabelAttribute(): string
    {
        return ucfirst($this->type_assurance);
    }
}
