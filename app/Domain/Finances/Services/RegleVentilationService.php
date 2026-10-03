<?php
// app/Domain/Finances/Services/RegleVentilationService.php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\RegleVentilation;
use App\Domain\Finances\Repositories\RegleVentilationRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegleVentilationService
{
    public function __construct(
        private RegleVentilationRepository $regles,
    ) {}

    public function creer(array $data): RegleVentilation
    {
        $this->verifierCoherence100($data['nature'], $data['sens'], $data['pourcentage']);

        return DB::transaction(fn () => $this->regles->create([
            'nature'                => $data['nature'],
            'sens'                  => $data['sens'],
            'centre_analytique_id'  => $data['centre_analytique_id'],
            'pourcentage'           => $data['pourcentage'],
            'priorite'              => $data['priorite'] ?? 0,
        ]));
    }

    public function modifier(int $id, array $data): RegleVentilation
    {
        $this->verifierCoherence100(
            $data['nature'], $data['sens'],
            $data['pourcentage'], ignoreId: $id
        );

        $this->regles->update($id, [
            'nature'               => $data['nature'],
            'sens'                 => $data['sens'],
            'centre_analytique_id' => $data['centre_analytique_id'],
            'pourcentage'          => $data['pourcentage'],
            'priorite'             => $data['priorite'] ?? 0,
        ]);

        return $this->regles->findOrFail($id);
    }

    public function supprimer(int $id): void
    {
        $this->regles->delete($id);
    }

    /**
     * Vérifie que la somme des % pour une (nature, sens) ne dépasse jamais
     * 100 % — l'utilisateur est prévenu avant d'atteindre un état
     * incohérent qui bloquerait toute application automatique.
     */
    private function verifierCoherence100(string $nature, string $sens, float $nouveauPct, ?int $ignoreId = null): void
    {
        $existantes = $this->regles->pourNature($nature, $sens)
            ->when($ignoreId, fn ($c) => $c->reject(fn ($r) => $r->id === $ignoreId));

        $total = $existantes->sum('pourcentage') + $nouveauPct;

        if ($total > 100.01) {
            throw new RuntimeException(
                "La somme des quotes-parts pour la nature « {$nature} » ({$sens}) dépasserait 100 % " .
                "(total actuel après ajout : {$total} %)."
            );
        }
    }
}