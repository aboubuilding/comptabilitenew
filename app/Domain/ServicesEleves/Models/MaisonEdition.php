<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaisonEdition extends Model
{
    protected $table = 'maison_editions';

    protected $fillable = ['libelle', 'etat'];

    public function livres(): HasMany
    {
        return $this->hasMany(Livre::class, 'maison_edition_id');
    }
}