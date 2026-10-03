<?php

namespace App\Support;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Feuille Excel construite à partir d'un tableau de lignes dont la première
 * est l'en-tête. Sert à la conversion des exports CSV (ExportCsvVersExcel).
 */
class TableauExport implements FromArray, ShouldAutoSize, WithStyles
{
    /** @param array<int, array<int, string|float|int|null>> $lignes */
    public function __construct(private array $lignes)
    {
    }

    public function array(): array
    {
        return $this->lignes;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
