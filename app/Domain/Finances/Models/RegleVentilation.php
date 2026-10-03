<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\SensAnalytique;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegleVentilation extends Model
{
    protected $table = 'regles_ventilation';

    protected $fillable = [
        'nature', 'sens', 'centre_analytique_id',
        'pourcentage', 'priorite', 'annee_id', 'etat',
    ];

    protected $casts = [
        'sens'        => SensAnalytique::class,
        'pourcentage' => 'float',
    ];

    public function centre(): BelongsTo
    {
        return $this->belongsTo(CentreAnalytique::class, 'centre_analytique_id');
    }
}