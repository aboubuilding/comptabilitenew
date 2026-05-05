<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignationEleveBus extends Model
{
    protected $table = 'assignations_eleves_bus';

    protected $fillable = [
        'abonnement_bus_id', 'voiture_id', 'zone_id', 'date_debut',
        'date_fin', 'sens', 'motif', 'statut'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'sens' => 'integer',
        'statut' => 'integer',
    ];

    public function abonnementBus()
    {
        return $this->belongsTo(AbonnementBus::class);
    }

    public function voiture()
    {
        return $this->belongsTo(Voiture::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    // Accesseur pour récupérer l'élève via l'abonnement
    public function getEleveAttribute()
    {
        return $this->abonnementBus?->inscription?->eleve;
    }
}