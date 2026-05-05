<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vente extends Model
{
    protected $fillable = ['reference', 'date_vente', 'magasin_id', 'total_ht', 'total_ttc', 'type_vente', 'statut_paiement', 'client_id', 'annee_id', 'utilisateur_id', 'etat'];

    public function details() { return $this->hasMany(VenteDetail::class); }
    public function magasin() { return $this->belongsTo(Magasin::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function utilisateur() { return $this->belongsTo(User::class); }
}