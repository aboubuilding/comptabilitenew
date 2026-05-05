<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Magasin extends Model
{
    protected $fillable = ['libelle', 'responsable', 'description', 'adresse', 'telephone', 'type', 'etat'];

    protected $casts = [
        'type' => 'integer',
        'etat' => 'integer',
    ];

    public function ventes()
    {
        return $this->hasMany(Vente::class);
    }

    public function stockActuel()
    {
        return $this->hasMany(StockActuel::class, 'magasin_id');
    }
}