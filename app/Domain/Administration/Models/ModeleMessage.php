<?php

namespace App\Domain\Administration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle de message réutilisable.
 */
class ModeleMessage extends Model
{
    protected $table = 'modeles_messages';

    protected $fillable = [
        'code', 'libelle', 'sujet', 'corps',
        'variables_disponibles', 'type', 'etat', 'annee_id',
    ];

    protected $casts = [
        'variables_disponibles' => 'array',
        'etat'                  => 'integer',
        'annee_id'              => 'integer',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'modele_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }

    /**
     * Variables par défaut proposées à l'utilisateur.
     */
    public static function variablesParDefaut(): array
    {
        return [
            '{{parent}}'     => 'Nom du parent',
            '{{eleve}}'      => 'Nom de l\'élève',
            '{{classe}}'     => 'Classe de l\'élève',
            '{{niveau}}'     => 'Niveau de l\'élève',
            '{{annee}}'      => 'Année scolaire',
            '{{montant_du}}' => 'Montant dû (relances)',
            '{{echeance}}'   => 'Date d\'échéance',
            '{{etablissement}}' => 'Nom de l\'établissement',
        ];
    }
}