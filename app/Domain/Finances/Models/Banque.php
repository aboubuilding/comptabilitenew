<?php

namespace App\Domain\Finances\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banque extends Model
{
    protected $table = 'banques';

    protected $fillable = ['nom', 'etat'];

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class, 'banque_id');
    }

    /* ===================== Accesseurs statistiques ===================== */

    public function getChequesEnAttenteCountAttribute(): int
    {
        return $this->cheques()
            ->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS->value)
            ->count();
    }

    public function getMontantEnAttenteAttribute(): float
    {
        return round(
            (float) $this->cheques()
                ->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS->value)
                ->sum('montant'),
            2
        );
    }
}