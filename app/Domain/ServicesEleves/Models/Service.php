<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = ['libelle', 'description', 'prix_unitaire', 'etat'];

    protected $casts = [
        'prix_unitaire' => 'float',
    ];
}