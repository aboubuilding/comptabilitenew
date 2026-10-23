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
        'dossier_recouvrement_id', 'inscription_id', 'type_relance', 'date_relance', 'contenu',
        'destinataire', 'statut_envoi', 'lot',
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

    /** Élève relancé (avec ou sans dossier). */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Scolarite\Models\Inscription::class, 'inscription_id');
    }

    public function libelleStatutEnvoi(): string
    {
        return match ($this->statut_envoi) {
            'envoye'            => 'Envoyé',
            'echec'             => 'Échec',
            'sans_destinataire' => 'Sans destinataire',
            default             => 'À envoyer',
        };
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}