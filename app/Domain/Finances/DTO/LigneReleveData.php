<?php

namespace App\Domain\Finances\DTO;

final readonly class LigneReleveData
{
    public function __construct(
        public string $numero,
        public string $dateEncaissement, // format Y-m-d
        public float  $montant,
        public ?string $libelle = null,
    ) {}

    public static function fromArray(array $row): ?self
    {
        // Format attendu : numero, date, montant [, libelle]
        // On tolère les variations de nommage à la lecture.
        $numero = trim((string) ($row['numero'] ?? $row['numero_cheque'] ?? ''));
        $date   = trim((string) ($row['date']   ?? $row['date_encaissement'] ?? ''));
        $montant = (float) str_replace([' ', ','], ['', '.'], (string) ($row['montant'] ?? 0));

        if ($numero === '' || $date === '' || $montant <= 0) {
            return null; // ligne ignorée silencieusement, elle sera comptée
        }

        // Normalisation de la date : accepte 2026-01-15, 15/01/2026
        $dateNorm = \DateTime::createFromFormat('Y-m-d', $date)
            ?: \DateTime::createFromFormat('d/m/Y', $date);

        if (! $dateNorm) {
            return null;
        }

        return new self(
            numero:           $numero,
            dateEncaissement: $dateNorm->format('Y-m-d'),
            montant:          round($montant, 2),
            libelle:          trim((string) ($row['libelle'] ?? '')) ?: null,
        );
    }
}