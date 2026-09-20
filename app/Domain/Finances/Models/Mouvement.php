<?php

namespace App\Domain\Finances\Models;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Finances\Types\MouvementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mouvement extends Model
{
    protected $table = 'mouvements';

    protected $fillable = [
        'libelle', 'beneficiaire', 'motif',
        'date_mouvement', 'montant',
        'type_mouvement',
        'caisse_id', 'utilisateur_id',
        'paiement_id', 'depense_id',
        'annee_id', 'file',
        'statut_mouvement', 'etat',
    ];

    protected $casts = [
        'type_mouvement' => MouvementType::class,
        'date_mouvement' => 'date',
        'montant'        => 'float',
    ];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}