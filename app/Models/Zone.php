<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $table = 'zones';

    protected $fillable = [
        'code', 'libelle', 'description', 'tarif_base', 'ordre',
        'couleur', 'points_arret', 'chauffeur_id', 'voiture_id',
        'annee_id', 'etat'
    ];

    protected $casts = [
        'points_arret' => 'array',
        'tarif_base' => 'decimal:2',
        'etat' => 'integer',
    ];

    public function chauffeur()
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function voiture()
    {
        return $this->belongsTo(Voiture::class);
    }

    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    // Étendue : zones actives
    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}