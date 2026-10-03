<?php

namespace App\Domain\Logistique\Models;

use App\Domain\Logistique\Types\TypeBon;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bon extends Model
{
    protected $table = 'bons';

    protected $fillable = [
        'reference', 'date_bon', 'type',
        'magasin_id', 'magasin_destination_id',
        'nom_responsable', 'nom_magasinier',
        'motif', 'commentaire', 'utilisateur_id',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'type'     => TypeBon::class,
        'date_bon' => 'date',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function magasinDestination(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_destination_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailBon::class, 'bon_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Stock::class, 'bon_id');
    }
}