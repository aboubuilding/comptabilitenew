<?php

namespace App\Domain\Administration\Models;

use App\Domain\Administration\Types\StatutMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Message (email) — brouillon, en cours, envoyé.
 */
class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'sujet', 'corps', 'type', 'modele_id',
        'expediteur_id', 'expediteur_nom',
        'statut', 'date_envoi',
        'nb_destinataires', 'nb_envoyes', 'nb_echecs',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'statut'          => StatutMessage::class,
        'date_envoi'      => 'datetime',
        'nb_destinataires' => 'integer',
        'nb_envoyes'      => 'integer',
        'nb_echecs'       => 'integer',
        'etat'            => 'integer',
        'annee_id'        => 'integer',
    ];

    public function modele(): BelongsTo
    {
        return $this->belongsTo(ModeleMessage::class, 'modele_id');
    }

    public function expediteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expediteur_id');
    }

    public function destinataires(): HasMany
    {
        return $this->hasMany(MessageDestinataire::class, 'message_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }

    /**
     * Taux de livraison en %.
     */
    public function getTauxLivraisonAttribute(): float
    {
        if ($this->nb_destinataires === 0) {
            return 0.0;
        }
        return round(($this->nb_envoyes / $this->nb_destinataires) * 100, 1);
    }
}