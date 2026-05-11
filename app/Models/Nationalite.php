<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nationalite extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nationalites';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'etat' => 'boolean',
    ];

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si la nationalité est active.
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Activer la nationalité.
     */
    public function activer(): void
    {
        $this->etat = self::ETAT_ACTIF;
        $this->save();
    }

    /**
     * Désactiver la nationalité.
     */
    public function desactiver(): void
    {
        $this->etat = self::ETAT_INACTIF;
        $this->save();
    }
}
