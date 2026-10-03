<?php

namespace App\Domain\Administration\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JournalExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $actions) {}

    public function collection(): Collection { return $this->actions; }

    public function headings(): array
    {
        return ['Date', 'Utilisateur', 'Login', 'Module', 'Action', 'Entité', 'Description', 'IP', 'Route'];
    }

    public function map($a): array
    {
        return [
            $a->created_at?->format('d/m/Y H:i:s'),
            $a->user_nom,
            $a->user_login,
            $a->module,
            $a->action,
            $a->entite ? ($a->entite . ($a->entite_id ? ' #' . $a->entite_id : '')) : '',
            $a->description,
            $a->ip,
            $a->route,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B0000']]],
        ];
    }

    public function title(): string { return 'Journal'; }
}