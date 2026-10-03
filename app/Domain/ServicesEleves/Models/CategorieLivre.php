<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieLivre extends Model
{
    protected $table = 'categorie_livres';

    protected $fillable = ['libelle', 'etat'];

    public function livres(): HasMany
    {
        return $this->hasMany(Livre::class, 'categorie_livre_id');
    }
}