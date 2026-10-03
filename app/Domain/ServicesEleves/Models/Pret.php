<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\Scolarite\Models\Inscription;
use App\Domain\ServicesEleves\Types\StatutPret;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pret extends Model
{
    protected $table = 'prets';

    protected $fillable = [
        'livre_id', 'inscription_id', 'annee_id',
        'date_pret', 'date_retour_pret', 'date_retour_reel',
        'statut_pret',
        'caution', 'observations', 'utilisateur_id', 'date_retard_constate',
        'etat',
    ];

    protected $casts = [
        'statut_pret'         => StatutPret::class,
        'date_pret'           => 'date',
        'date_retour_pret'    => 'date',
        'date_retour_reel'    => 'date',
        'date_retard_constate'=> 'date',
        'caution'             => 'float',
    ];

    public function livre(): BelongsTo
    {
        return $this->belongsTo(Livre::class, 'livre_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function isEnCours(): bool
    {
        return in_array($this->statut_pret, [StatutPret::EN_COURS, StatutPret::RETARD], true);
    }

    public function isEnRetard(): bool
    {
        return $this->isEnCours()
            && $this->date_retour_pret
            && $this->date_retour_pret->isPast();
    }

    /** Nombre de jours de retard (0 si pas en retard). */
    public function getJoursRetardAttribute(): int
    {
        if (! $this->isEnRetard()) return 0;
        return (int) $this->date_retour_pret->diffInDays(now());
    }
}