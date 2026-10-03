<?php

namespace App\Domain\Administration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal des actions sensibles (CDC 6.4).
 * Domaine : Administration (App\Domain\Administration\Models).
 */
class JournalAction extends Model
{
    protected $table = 'journal_actions';

    public $timestamps = false; // on gère uniquement created_at

    protected $fillable = [
        'user_id', 'user_nom', 'user_login',
        'module', 'action', 'entite', 'entite_id',
        'description', 'payload',
        'ip', 'user_agent', 'route', 'methode_http',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
        'entite_id'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeUtilisateur($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}