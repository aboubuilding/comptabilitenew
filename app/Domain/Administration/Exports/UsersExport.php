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

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $users) {}

    public function collection(): Collection { return $this->users; }

    public function headings(): array
    {
        return ['Nom', 'Prénom', 'Login', 'Email', 'Profil', 'Statut', 'Dernière connexion', 'Dernière IP'];
    }

    public function map($user): array
    {
        return [
            $user->nom,
            $user->prenom,
            $user->login,
            $user->email,
            $user->roleRelation->libelle ?? '—',
            $user->etat ? 'Actif' : 'Inactif',
            $user->last_login_at?->format('d/m/Y H:i') ?? '—',
            $user->last_login_ip ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B0000']]],
        ];
    }

    public function title(): string { return 'Utilisateurs'; }
}