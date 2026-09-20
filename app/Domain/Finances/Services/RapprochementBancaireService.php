<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\LigneReleveData;
use App\Domain\Finances\Models\Cheque;
use App\Domain\Finances\Repositories\ChequeRepository;
use App\Domain\Finances\Types\ChequeStatut;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RapprochementBancaireService
{
    public function __construct(
        private ChequeRepository $cheques,
        private ChequeService    $chequeService,
    ) {}

    /**
     * Analyse un ensemble de lignes de relevé sans rien écrire en base.
     * Renvoie un tableau structuré pour la prévisualisation.
     *
     * @param  LigneReleveData[] $lignes
     * @return array{
     *     matched: array<int, array{ligne: LigneReleveData, cheque: Cheque}>,
     *     ambiguous: array<int, array{ligne: LigneReleveData, candidats: \Illuminate\Support\Collection}>,
     *     unmatched: array<int, LigneReleveData>
     * }
     */
    public function analyser(int $banqueId, array $lignes): array
    {
        $matched   = [];
        $ambiguous = [];
        $unmatched = [];

        // On charge en une fois tous les chèques en attente de cette banque
        // (typiquement quelques dizaines/centaines) — plus rapide qu'une
        // requête par ligne.
        $chequesEnAttente = $this->cheques->activeQuery()
            ->where('banque_id', $banqueId)
            ->where('statut', ChequeStatut::EMIS->value)
            ->get();

        foreach ($lignes as $ligne) {
            // 1) Match strict : même numéro ET même montant.
            $exact = $chequesEnAttente->first(function (Cheque $c) use ($ligne) {
                return trim((string) $c->numero) === $ligne->numero
                    && abs((float) $c->montant - $ligne->montant) < 0.01;
            });

            if ($exact) {
                $matched[] = ['ligne' => $ligne, 'cheque' => $exact];
                // Un chèque ne peut matcher qu'une fois par import.
                $chequesEnAttente = $chequesEnAttente->reject(fn (Cheque $c) => $c->id === $exact->id);
                continue;
            }

            // 2) Match par numéro seul → ambigu (montant différent).
            $parNumero = $chequesEnAttente->filter(
                fn (Cheque $c) => trim((string) $c->numero) === $ligne->numero
            );

            if ($parNumero->isNotEmpty()) {
                $ambiguous[] = ['ligne' => $ligne, 'candidats' => $parNumero];
                continue;
            }

            // 3) Match par montant seul → ambigu (numéro différent).
            $parMontant = $chequesEnAttente->filter(
                fn (Cheque $c) => abs((float) $c->montant - $ligne->montant) < 0.01
            );

            if ($parMontant->isNotEmpty()) {
                $ambiguous[] = ['ligne' => $ligne, 'candidats' => $parMontant];
                continue;
            }

            // 4) Rien trouvé.
            $unmatched[] = $ligne;
        }

        return [
            'matched'   => $matched,
            'ambiguous' => $ambiguous,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Applique un rapprochement en masse : chaque paire (ligne, chequeId)
     * reçue du formulaire passe à ENCAISSE. L'observer ChequeObserver
     * s'occupe de créer les mouvements de caisse associés.
     *
     * @param  array<int, array{cheque_id:int, date:string}> $paires
     * @return array{ok:int, errors:array<int,string>}
     */
    public function appliquerEnMasse(array $paires, int $userId): array
    {
        $ok     = 0;
        $errors = [];

        DB::transaction(function () use ($paires, $userId, &$ok, &$errors) {
            foreach ($paires as $paire) {
                try {
                    $this->chequeService->marquerEncaisse(
                        (int) $paire['cheque_id'],
                        $userId,
                        $paire['date'] ?? null
                    );
                    $ok++;
                } catch (RuntimeException $e) {
                    $errors[] = "Chèque #{$paire['cheque_id']} : " . $e->getMessage();
                }
            }
        });

        return ['ok' => $ok, 'errors' => $errors];
    }
}