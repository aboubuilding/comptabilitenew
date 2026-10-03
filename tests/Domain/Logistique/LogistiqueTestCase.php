<?php

namespace Tests\Domain\Logistique;

use App\Domain\Logistique\Models\StockActuel;
use App\Domain\Logistique\Types\TypeMagasin;
use Illuminate\Support\Facades\DB;
use Tests\Domain\DomainTestCase;

/**
 * Jeux de données communs au menu 3 — Logistique & Achats.
 */
abstract class LogistiqueTestCase extends DomainTestCase
{
    protected function creerProduit(string $libelle = 'T-shirt M', float $prix = 6500): int
    {
        static $n = 0;
        $n++;

        return DB::table('produits')->insertGetId([
            'code'          => "PRD{$n}",
            'libelle'       => $libelle,
            'prix_unitaire' => $prix,
            'etat'          => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    protected function creerMagasin(string $libelle = 'Entrepôt central', TypeMagasin $type = TypeMagasin::ENTREPOT): int
    {
        return DB::table('magasins')->insertGetId([
            'libelle'    => $libelle,
            'type'       => $type->value,
            'etat'       => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function creerFournisseur(string $raisonSociale = 'Papeterie du Centre'): int
    {
        return DB::table('fournisseurs')->insertGetId([
            'raison_social' => $raisonSociale,
            'etat'          => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    protected function quantiteEnStock(int $produitId, int $magasinId): float
    {
        return (float) (StockActuel::where('produit_id', $produitId)->where('magasin_id', $magasinId)->value('quantite') ?? 0);
    }
}
