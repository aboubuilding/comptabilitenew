<?php

namespace App\Domain\Administration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Référentiel des profils / rôles applicatifs.
 * Domaine : Administration (App\Domain\Administration\Models).
 */
class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'niveau',
        'etat',
    ];

    protected $casts = [
        'niveau' => 'integer',
        'etat'   => 'integer',
    ];

    /**
     * Utilisateurs rattachés à ce rôle.
     * NB : User reste App\Domain\Administration\Models\User (exception Laravel standard).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}