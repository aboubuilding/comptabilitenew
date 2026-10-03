<?php

namespace App\Domain\Rh\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periode extends Model
{
    protected $table = 'periodes';

    protected $fillable = ['libelle', 'date_debut', 'date_fin', 'annee_id', 'etat'];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function paies(): HasMany
    {
        return $this->hasMany(Paie::class, 'periode_id');
    }

    /** Nombre de bulletins dans cette période. */
    public function getNombreBulletinsAttribute(): int
    {
        return $this->paies()->where('etat', 1)->count();
    }

    /** Masse salariale nette de cette période. */
    public function getMasseNetteAttribute(): float
    {
        return round((float) $this->paies()->where('etat', 1)->sum('net_a_payer'), 2);
    }
}