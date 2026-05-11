<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcartCaisse extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecart_caisse';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'caisse_id',
        'ecart_constate',
        'type',
        'motif',
        'statut',
        'declare_par',
        'valide_par',
        'date_cloture',
        'date_validation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ecart_constate' => 'decimal:2',
        'date_cloture' => 'datetime',
        'date_validation' => 'datetime',
    ];

    // ===== CONSTANTES POUR `type` =====
    const TYPE_MANQUANT = 'manquant';
    const TYPE_EXCEDENT = 'excedent';

    // ===== CONSTANTES POUR `statut` =====
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_VALIDE = 'valide';
    const STATUT_REFUSE = 'refuse';
    const STATUT_ENQUETE = 'enquete';

    // ===== RELATIONS =====

    /**
     * Relation avec la caisse.
     */
    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    /**
     * Relation avec l'utilisateur qui a déclaré l'écart.
     */
    public function declarePar()
    {
        return $this->belongsTo(User::class, 'declare_par');
    }

    /**
     * Relation avec l'utilisateur qui a validé l'écart.
     */
    public function validePar()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    // ===== MÉTHODES UTILITAIRES =====

    /**
     * Vérifier si l'écart est un manquant.
     */
    public function isManquant(): bool
    {
        return $this->type === self::TYPE_MANQUANT;
    }

    /**
     * Vérifier si l'écart est un excédent.
     */
    public function isExcedent(): bool
    {
        return $this->type === self::TYPE_EXCEDENT;
    }

    /**
     * Vérifier si le statut est "en attente".
     */
    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    /**
     * Vérifier si le statut est "validé".
     */
    public function estValide(): bool
    {
        return $this->statut === self::STATUT_VALIDE;
    }

    /**
     * Libellé du type.
     */
    public function getTypeLabelAttribute(): string
    {
        return [
            self::TYPE_MANQUANT => 'Manquant',
            self::TYPE_EXCEDENT => 'Excédent',
        ][$this->type] ?? $this->type;
    }

    /**
     * Libellé du statut.
     */
    public function getStatutLabelAttribute(): string
    {
        return [
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_VALIDE => 'Validé',
            self::STATUT_REFUSE => 'Refusé',
            self::STATUT_ENQUETE => 'Enquête',
        ][$this->statut] ?? $this->statut;
    }
}
