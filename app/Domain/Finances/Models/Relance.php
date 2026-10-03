<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\ResultatRelance;
use App\Domain\Finances\Types\TypeRelance;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Relance extends Model
{
    protected $table = 'relances';

    protected $fillable = [
        'dossier_recouvrement_id', 'type_relance', 'date_relance', 'contenu',
        'montant_du_moment', 'resultat', 'date_prochaine_relance',
        'utilisateur_id', 'message_id', 'annee_id', 'etat',
    ];

    protected $casts = [
        'type_relance'          => TypeRelance::class,
        'resultat'              => ResultatRelance::class,
        'date_relance'          => 'date',
        'date_prochaine_relance' => 'date',
        'montant_du_moment'     => 'float',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DossierRecouvrement::class, 'dossier_recouvrement_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}