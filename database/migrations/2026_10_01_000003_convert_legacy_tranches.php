<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convertit les tranches à l'ancien format (une ligne par niveau et par
     * année, avec tranche_1..3 / date_1..3) vers le format actuel : une ligne
     * par échéance, rattachée au frais de scolarité du même niveau et de la
     * même année, avec un taux en pourcentage du montant de ce frais.
     *
     * Les anciennes lignes sont conservées avec leurs montants, mais désactivées.
     */
    public function up(): void
    {
        // Le formulaire saisit des taux à deux décimales : la colonne entière les tronquait
        Schema::table('tranches', function (Blueprint $table) {
            $table->decimal('taux', 5, 2)->nullable()->change();
        });

        if (!Schema::hasColumn('tranches', 'tranche_1')) {
            return;
        }

        $anciennes = DB::table('tranches')
            ->whereNull('frais_ecole_id')
            ->whereNotNull('niveau_id')
            ->where('etat', 1)
            ->orderBy('id')
            ->get();

        foreach ($anciennes as $ancienne) {
            $echeances = collect([1, 2, 3])
                ->map(fn (int $n) => [
                    'numero'  => $n,
                    'montant' => (float) $ancienne->{"tranche_{$n}"},
                    'date'    => $ancienne->{"date_{$n}"},
                ])
                ->filter(fn (array $e) => $e['montant'] > 0)
                ->values();

            $total = $echeances->sum('montant');

            // Frais de scolarité correspondant : même niveau, même année, même montant total
            $frais = DB::table('frais_ecoles')
                ->where('niveau_id', $ancienne->niveau_id)
                ->where('annee_id', $ancienne->annee_id)
                ->where('etat', 1)
                ->get()
                ->filter(fn ($f) => abs((float) $f->montant - $total) < 0.01);

            // Sans correspondance unique, la ligne est laissée telle quelle
            if ($total <= 0 || $frais->count() !== 1) {
                continue;
            }

            DB::transaction(function () use ($ancienne, $echeances, $total, $frais) {
                $cumul = 0.0;

                foreach ($echeances as $i => $echeance) {
                    // La dernière échéance absorbe l'arrondi pour totaliser 100 %
                    $taux = $i === $echeances->count() - 1
                        ? round(100 - $cumul, 2)
                        : round($echeance['montant'] / $total * 100, 2);
                    $cumul += $taux;

                    DB::table('tranches')->insert([
                        'libelle'        => 'Tranche ' . $echeance['numero'],
                        'date_butoire'   => $echeance['date'],
                        'frais_ecole_id' => $frais->first()->id,
                        'taux'           => $taux,
                        'etat'           => 1,
                        'created_at'     => $ancienne->created_at,
                        'updated_at'     => now(),
                    ]);
                }

                DB::table('tranches')->where('id', $ancienne->id)->update(['etat' => 2]); // BaseRepository::INACTIF
            });
        }
    }

    public function down(): void
    {
        // Volontairement vide : les tranches converties ne se distinguent plus de celles saisies ensuite
    }
};
