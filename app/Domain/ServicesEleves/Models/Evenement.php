<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    use HasFactory;

    protected $table = 'evenements';

    protected $fillable = [
        'nom', 'type', 'date_evenement', 'participation', 'capacite',
        'description', 'annee_id', 'etat',
    ];

    protected $casts = [
        'date_evenement' => 'date',
        'participation'  => 'float',
        'etat'           => 'integer',
    ];

    public function participations()
    {
        return $this->hasMany(ParticipationEvenement::class, 'evenement_scolaire_id');
    }

    /**
     * null = capacité illimitée — condition explicite du cahier des
     * charges pour qu'un événement soit ajoutable librement au panier
     * ("un événement sans capacité limitée").
     */
    public function estSansLimiteCapacite(): bool
    {
        return is_null($this->capacite);
    }

    public function placesRestantes(): ?int
    {
        if ($this->estSansLimiteCapacite()) {
            return null;
        }

        return max(0, $this->capacite - $this->participations()->where('etat', 1)->count());
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}