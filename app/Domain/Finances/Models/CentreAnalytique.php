<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\TypeCentreAnalytique;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CentreAnalytique extends Model
{
    protected $table = 'centres_analytiques';

    protected $fillable = [
        'code', 'libelle', 'description', 'type',
        'responsable_id', 'annee_id', 'etat',
    ];

    protected $casts = [
        'type' => TypeCentreAnalytique::class,
    ];

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function imputations(): HasMany
    {
        return $this->hasMany(ImputationAnalytique::class, 'centre_analytique_id');
    }

    public function regles(): HasMany
    {
        return $this->hasMany(RegleVentilation::class, 'centre_analytique_id');
    }
}