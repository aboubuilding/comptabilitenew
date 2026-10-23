<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\NatureCreance;
use Illuminate\Database\Eloquent\Model;

/** Échéance réclamée par un dossier de recouvrement (scolarité, mois de cantine, de transport). */
class DossierEcheance extends Model
{
    protected $table = 'dossier_recouvrement_echeances';

    protected $fillable = [
        'dossier_recouvrement_id', 'nature', 'libelle', 'date_echeance', 'montant', 'montant_paye', 'reste',
    ];

    protected $casts = [
        'nature'        => NatureCreance::class,
        'date_echeance' => 'date',
        'montant'       => 'float',
        'montant_paye'  => 'float',
        'reste'         => 'float',
    ];
}
