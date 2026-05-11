<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntretienVehicule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'entretiens_vehicules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'voiture_id',
        'date_entretien',
        'type_entretien',
        'cout',
        'kilometrage',
        'observations',
        'effectue_par',
        'annee_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_entretien' => 'date',
        'cout'           => 'decimal:2',
        'kilometrage'    => 'integer',
    ];

    // ===== CONSTANTES POUR `type_entretien` =====
    const TYPE_VIDANGE     = 'vidange';
    const TYPE_REVISION    = 'révision';
    const TYPE_PNEUS       = 'pneus';
    const TYPE_FREINS      = 'freins';
    const TYPE_VIDE        = 'vidange';
    const TYPE_AUTRE       = 'autre';

    // ===== RELATIONS =====
    /**
     * Relation avec la voiture.
     */
    public function voiture()
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    /**
     * Relation avec le chauffeur qui a effectué l'entretien.
     */
    public function effectuePar()
    {
        return $this->belongsTo(Chauffeur::class, 'effectue_par');
    }

    /**
     * Relation avec l'année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le type d'entretien correspond.
     */
    public function estDeType(string $type): bool
    {
        return $this->type_entretien === $type;
    }

    /**
     * Libellé formaté du coût.
     */
    public function getCoutFormattedAttribute(): string
    {
        return number_format($this->cout, 0, ',', ' ') . ' FCFA';
    }
}
