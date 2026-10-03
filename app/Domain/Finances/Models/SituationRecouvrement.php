<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\NatureCreance;
use Illuminate\Database\Eloquent\Model;

class SituationRecouvrement extends Model
{
    protected $table = 'situations_recouvrement';

    protected $fillable = [
        'inscription_id', 'nature',
        'montant_du', 'montant_paye', 'retard', 'jours_retard',
        'date_calcul', 'annee_id', 'etat',
    ];

    protected $casts = [
        'nature'       => NatureCreance::class,
        'montant_du'   => 'float',
        'montant_paye' => 'float',
        'retard'       => 'float',
        'date_calcul'  => 'date',
    ];

    /* ===================== Helpers ===================== */

    /** Tranche d'ancienneté (balance âgée) — pour l'affichage indexé. */
    public function trancheAnciennete(): string
    {
        return match (true) {
            $this->jours_retard <= 30 => '0-30',
            $this->jours_retard <= 60 => '31-60',
            $this->jours_retard <= 90 => '61-90',
            default                   => '+90',
        };
    }

    public function isEnRetard(): bool
    {
        return $this->retard > 0.01;
    }
}