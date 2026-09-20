<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Espace extends Model
{
    use HasFactory;

    protected $table = 'espaces';

    protected $fillable = ['nom_famille', 'annee_id', 'etat'];

    protected $casts = ['etat' => 'integer'];

    public function parents()
    {
        return $this->hasMany(ParentEleve::class, 'espace_id');
    }

    public function eleves()
    {
        return $this->hasMany(Eleve::class, 'espace_id');
    }

    public function comptes()
    {
        return $this->hasMany(Compte::class, 'espace_id');
    }

    public function parentPrincipal()
    {
        return $this->hasOne(ParentEleve::class, 'espace_id')->where('is_principal', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}