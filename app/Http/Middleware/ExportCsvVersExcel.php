<?php

namespace App\Http\Middleware;

use App\Support\TableauExport;
use Closure;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as Format;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export des listes au format Excel (.xlsx) — cahier des charges § 6,
 * « Export des données ».
 *
 * Les contrôleurs produisent leurs exports en CSV (un flux écrit ligne à
 * ligne, séparateur « ; »). Plutôt que de réécrire chacun d'eux, ce
 * middleware convertit le flux en classeur Excel : mêmes colonnes, mêmes
 * filtres, mais un vrai fichier .xlsx, avec les montants en nombres.
 *
 * Ajouter ?format=csv à l'adresse d'un export conserve le CSV d'origine.
 */
class ExportCsvVersExcel
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            ! $response instanceof StreamedResponse
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/csv')
            || $request->query('format') === 'csv'
        ) {
            return $response;
        }

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        $lignes = $this->lire($csv);
        if ($lignes === []) {
            $lignes = [['Aucune donnée']];
        }

        $nom = $this->nomDuFichier((string) $response->headers->get('Content-Disposition'));

        return response(
            Excel::raw(new TableauExport($lignes), Format::XLSX),
            200,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $nom . '"',
            ]
        );
    }

    /** @return array<int, array<int, string|float>> */
    private function lire(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv); // marque d'ordre des octets posée pour Excel

        $flux = fopen('php://temp', 'r+');
        fwrite($flux, $csv);
        rewind($flux);

        $lignes = [];
        while (($ligne = fgetcsv($flux, 0, ';')) !== false) {
            if ($ligne === [null]) {
                continue;
            }
            $lignes[] = array_map(fn ($cellule) => $this->valeur((string) $cellule), $ligne);
        }
        fclose($flux);

        return $lignes;
    }

    /**
     * Un montant écrit « 12 000,50 » devient un nombre, pour être sommable
     * dans Excel. Les autres valeurs (matricules, téléphones, années…)
     * restent du texte : on ne convertit que ce qui porte une décimale ou
     * un séparateur de milliers.
     */
    private function valeur(string $cellule): string|float
    {
        $texte = trim($cellule);

        if (preg_match('/^-?\d{1,3}(?:[ \x{00A0}\x{202F}]\d{3})*(?:,\d+)?$/u', $texte) && preg_match('/[ ,\x{00A0}\x{202F}]/u', $texte)) {
            return (float) str_replace(',', '.', preg_replace('/[ \x{00A0}\x{202F}]/u', '', $texte));
        }

        return $cellule;
    }

    private function nomDuFichier(string $disposition): string
    {
        $nom = preg_match('/filename="?([^";]+)"?/', $disposition, $m) ? $m[1] : 'export.csv';

        return preg_replace('/\.csv$/i', '', $nom) . '.xlsx';
    }
}
