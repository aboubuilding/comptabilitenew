<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\Logistique\Models\Magasin;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreparationRepas extends Model
{
    protected $table = 'preparations_repas';

    protected $fillable = [
        'menu_id', 'magasin_id', 'date_preparation', 'nombre_parts',
        'cout_reel', 'stock_decremente', 'observations',
        'responsable_id', 'annee_id',
    ];

    protected $casts = [
        'date_preparation' => 'date',
        'cout_reel'        => 'float',
        'stock_decremente' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}