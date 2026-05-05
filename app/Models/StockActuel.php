<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockActuel extends Model
{
    protected $table = 'stock_actuel';
    protected $fillable = ['produit_id', 'magasin_id', 'quantite', 'seuil_alerte'];

    public function produit() { return $this->belongsTo(Produit::class); }
    public function magasin() { return $this->belongsTo(Magasin::class); }
}