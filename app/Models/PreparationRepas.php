<?php
namespace App\Models;

class PreparationRepas extends Model
{
    protected $fillable = [
        'menu_id', 'date_preparation', 'nombre_parts', 'cout_reel', 'observations', 'responsable_id'
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}