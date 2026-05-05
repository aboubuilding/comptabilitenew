<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prevision extends Model
{
    protected $fillable = [
        'libelle', 'type', 'montant', 'date_prevision', 'date_fin',
        'periode', 'annee_id', 'categorie_id', 'commentaire', 'etat'
    ];

    protected $casts = [
        'date_prevision' => 'date',
        'date_fin' => 'date',
        'montant' => 'decimal:2',
    ];

    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }
}