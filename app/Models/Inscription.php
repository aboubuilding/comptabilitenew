<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    protected $table = 'inscriptions';
    protected $fillable = [
        'date_inscription', 'eleve_id', 'cycle_id', 'niveau_id', 'last_niveau_id', 'classe_id',
        'espace_id', 'type_inscription', 'statut_validation', 'annee_id', 'parent_id',
        'taux_remise', 'motif_rejet', 'date_validation', 'utilisateur_id',
        'specialite_id_1', 'specialite_id_2', 'specialite_id_3', 'specialite_abandonne',
        'bulletin_1', 'bulletin_2', 'bulletin_3', 'dnb', 'programme_provenance',
        'is_cantine', 'is_bus', 'is_livre',
        'frais_scolarite', 'frais_assurance', 'frais_inscription', 'frais_cantine',
        'frais_bus', 'frais_livre', 'remise_scolarite', 'frais_examen', 'caution',
        'etat', 'date_abandon', 'motif_abandon', 'statut_abandon', 'abandonne_par'
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'date_validation' => 'datetime',
        'date_abandon' => 'date',
        'frais_scolarite' => 'float',
        'frais_assurance' => 'float',
        'frais_inscription' => 'float',
        'frais_cantine' => 'float',
        'frais_bus' => 'float',
        'frais_livre' => 'float',
        'remise_scolarite' => 'float',
        'frais_examen' => 'float',
        'caution' => 'float',
        'taux_remise' => 'integer',
        'statut_abandon' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────
    public function eleve() { return $this->belongsTo(Eleve::class); }
    public function cycle() { return $this->belongsTo(Cycle::class); }
    public function niveau() { return $this->belongsTo(Niveau::class); }
    public function classe() { return $this->belongsTo(Classe::class); }
    public function annee() { return $this->belongsTo(Annee::class); }

    // ─────────────────────────────────────────────────────────
    // Accesseurs pour les montants réels (après remise)
    // ─────────────────────────────────────────────────────────
    public function getMontantScolariteReelAttribute(): float
    {
        $base = $this->frais_scolarite ?? 0;
        $remise = ($this->taux_remise ?? 0) + ($this->remise_scolarite ?? 0);
        return round($base * (1 - $remise / 100), 2);
    }

    public function getMontantInscriptionReelAttribute(): float
    {
        $base = $this->frais_inscription ?? 0;
        $remise = $this->taux_remise ?? 0;
        return round($base * (1 - $remise / 100), 2);
    }

    public function getMontantAssuranceReelAttribute(): float
    {
        $base = $this->frais_assurance ?? 0;
        $remise = $this->taux_remise ?? 0;
        return round($base * (1 - $remise / 100), 2);
    }

    public function getMontantExamenReelAttribute(): float
    {
        $base = $this->frais_examen ?? 0;
        $remise = $this->taux_remise ?? 0;
        return round($base * (1 - $remise / 100), 2);
    }
}
