<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreparationRepas extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'preparations_repas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'menu_id',
        'date_preparation',
        'nombre_parts',
        'cout_reel',
        'observations',
        'responsable_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_preparation' => 'date',
        'cout_reel'        => 'decimal:2',
        'nombre_parts'     => 'integer',
    ];

    // ===== RELATIONS =====
    /**
     * Relation avec le menu.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * Relation avec l'utilisateur responsable de la préparation.
     */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Coût par part calculé (coût réel / nombre de parts).
     */
    public function getCoutParPartAttribute(): ?float
    {
        if ($this->cout_reel && $this->nombre_parts > 0) {
            return $this->cout_reel / $this->nombre_parts;
        }
        return null;
    }
}
