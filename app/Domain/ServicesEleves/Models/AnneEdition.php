<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneEdition extends Model
{
    protected $table = 'anne_editions';

    protected $fillable = ['libelle', 'etat'];

    public function livres(): HasMany
    {
        return $this->hasMany(Livre::class, 'annee_edition_id');
    }
}