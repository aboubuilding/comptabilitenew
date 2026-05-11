<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'description',
        'prix_unitaire',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'etat' => 'integer',
    ];

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    // ===== MÉTHODES UTILITAIRES =====

    /**
     * Vérifier si le service est actif.
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }
}
