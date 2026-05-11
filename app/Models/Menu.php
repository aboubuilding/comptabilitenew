<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'menus';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'description',
        'date_service',
        'type_repas',
        'quantite_prevue',
        'quantite_reellement',
        'cout_total_prevu',
        'cout_total_reel',
        'inscription_cantine_id',
        'annee_id',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_service'       => 'date',
        'type_repas'         => 'integer',
        'quantite_prevue'    => 'integer',
        'quantite_reellement'=> 'integer',
        'cout_total_prevu'   => 'decimal:2',
        'cout_total_reel'    => 'decimal:2',
        'etat'               => 'boolean',
    ];

    // ===== CONSTANTES POUR `type_repas` =====
    const TYPE_DEJEUNER = 1;
    const TYPE_GOUTER   = 2;
    const TYPE_PETIT_DEJEUNER = 3;
    const TYPE_DINER    = 4;

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'inscription à la cantine (si menu personnalisé).
     */
    public function inscriptionCantine()
    {
        return $this->belongsTo(InscriptionCantine::class, 'inscription_cantine_id');
    }

    /**
     * Relation avec l'année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le menu est actif.
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Libellé du type de repas.
     */
    public function getTypeRepasLabelAttribute(): string
    {
        return [
            self::TYPE_DEJEUNER        => 'Déjeuner',
            self::TYPE_GOUTER          => 'Goûter',
            self::TYPE_PETIT_DEJEUNER  => 'Petit-déjeuner',
            self::TYPE_DINER           => 'Dîner',
        ][$this->type_repas] ?? 'Indéfini';
    }

    /**
     * Calculer le nombre de parts restantes.
     */
    public function getPartsRestantesAttribute(): int
    {
        $servies = $this->quantite_reellement ?? 0;
        return max(0, $this->quantite_prevue - $servies);
    }
}
