<?php

namespace App\Domain\Finances\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEcheancier extends Model
{
    use HasFactory;

    protected $table = 'plan_echeanciers';

    protected $fillable = ['nom', 'description', 'annee_id', 'etat'];

    protected $casts = ['etat' => 'integer'];

    public function lignes()
    {
        return $this->hasMany(PlanEcheancierLigne::class)->orderBy('ordre');
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}