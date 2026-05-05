<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MouvementStock extends Model
{
    protected $table = 'mouvements_stock';
    protected $fillable = ['produit_id', 'magasin_id', 'magasin_dest_id', 'quantite', 'type_mouvement', 'reference', 'utilisateur_id', 'date_stock', 'annee_id', 'bon_id'];
}
