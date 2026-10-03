<?php

namespace App\Domain\Logistique\Models;

use App\Domain\Logistique\Types\TypeClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'nom_raison_sociale', 'type_client',
        'telephone', 'email', 'adresse',
        'nif', 'rccm',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'type_client' => TypeClient::class,
    ];

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class, 'client_id');
    }

    public function getNomCompletAttribute(): string
    {
        return $this->nom_raison_sociale;
    }
}