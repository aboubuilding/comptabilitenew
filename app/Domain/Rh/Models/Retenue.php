<?php

namespace App\Domain\Rh\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Retenue extends Model
{
    protected $table = 'retenues';

    protected $fillable = ['employe_id', 'irpp', 'annee_id', 'etat'];

    protected $casts = ['irpp' => 'float'];

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }
}