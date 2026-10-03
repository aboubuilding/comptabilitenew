<?php

namespace App\Domain\Administration\Models;

use App\Domain\Administration\Types\StatutEnvoi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Destinataire d'un message + statut de livraison.
 */
class MessageDestinataire extends Model
{
    protected $table = 'message_destinataires';

    protected $fillable = [
        'message_id',
        'destinataire_type', 'destinataire_id',
        'nom', 'email',
        'statut_envoi', 'erreur',
        'date_envoi', 'date_ouverture',
    ];

    protected $casts = [
        'statut_envoi'   => StatutEnvoi::class,
        'date_envoi'     => 'datetime',
        'date_ouverture' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
}