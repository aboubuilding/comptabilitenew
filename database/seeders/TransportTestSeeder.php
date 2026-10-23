<?php

namespace Database\Seeders;

use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Services\AnneeService;
use App\Domain\ServicesEleves\Types\StatutVehicule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données de test du transport scolaire, pour pouvoir souscrire au bus :
 * véhicules, chauffeurs, zones et lignes de l'année scolaire choisie
 * (par défaut l'année active).
 *
 *     php artisan db:seed --class=TransportTestSeeder
 *     ANNEE_ID=4 php artisan db:seed --class=TransportTestSeeder
 *
 * - Les zones reprennent les zones de ramassage de la base historique (frais
 *   de transport), avec leur tarif mensuel.
 * - Les lignes sont des parcours desservis par un chauffeur et un véhicule
 *   pendant l'année scolaire ; les zones leur sont réparties d'après leur
 *   tarif (une ligne ne porte pas de prix).
 * - Des chauffeurs de test (nom « TEST », permis « PERMIS-TEST-… ») conduisent
 *   ces véhicules ; les chauffeurs réels ne sont pas touchés.
 * - Les véhicules de test portent une plaque « TEST-… ».
 *
 * Relançable : rien n'est créé deux fois (code de zone, libellé de ligne,
 * plaque de véhicule et permis de chauffeur servent de clé) ; un nouveau
 * passage remet les affectations à jour.
 */
class TransportTestSeeder extends Seeder
{
    /** Libellés de frais de transport qui ne sont pas des zones de ramassage. */
    private const PAS_DES_ZONES = ['bus', 'bus_derniere_tranche', 'inscription au transport', 'inscription_transport', 'location de livres numériques'];

    /**
     * Lignes : libellé => [ancien libellé, tarifs des zones qu'elle dessert (min, max), couleur].
     * L'ancien libellé (premier passage du seeder) permet de renommer sans doublon.
     */
    private const LIGNES = [
        'Ligne A — Centre'      => ['Ligne A — tarif 15 000 à 25 000', 15000, 25000, '#2563eb'],
        'Ligne B — Ouest'       => ['Ligne B — tarif 26 000 à 30 000', 26000, 30000, '#1c7a4d'],
        'Ligne C — Nord'        => ['Ligne C — tarif 31 000 à 35 000', 31000, 35000, '#b8860b'],
        'Ligne D — Nord-Est'    => ['Ligne D — tarif 36 000 à 45 000', 36000, 45000, '#c81e3a'],
        'Ligne E — Périphérie'  => ['Ligne E — tarif 46 000 à 55 000', 46000, 55000, '#7a0f22'],
    ];

    private const VEHICULES = [
        ['Toyota', 'Hiace', 'TEST-0001-TG', 18, 2018],
        ['Toyota', 'Coaster', 'TEST-0002-TG', 30, 2016],
        ['Nissan', 'Urvan', 'TEST-0003-TG', 15, 2019],
        ['Mercedes', 'Sprinter', 'TEST-0004-TG', 20, 2017],
        ['Toyota', 'Hiace', 'TEST-0005-TG', 18, 2020],
    ];

    private const CHAUFFEURS_TEST = [
        ['TEST', 'Chauffeur Un', '90000001', 'PERMIS-TEST-01'],
        ['TEST', 'Chauffeur Deux', '90000002', 'PERMIS-TEST-02'],
        ['TEST', 'Chauffeur Trois', '90000003', 'PERMIS-TEST-03'],
        ['TEST', 'Chauffeur Quatre', '90000004', 'PERMIS-TEST-04'],
        ['TEST', 'Chauffeur Cinq', '90000005', 'PERMIS-TEST-05'],
    ];

    public function run(): void
    {
        $annee = getenv('ANNEE_ID') ? Annee::findOrFail((int) getenv('ANNEE_ID')) : app(AnneeService::class)->resolveActive();
        $maintenant = now();
        $bilan = ['vehicules' => 0, 'chauffeurs' => 0, 'zones' => 0, 'lignes' => 0];

        DB::transaction(function () use ($annee, $maintenant, &$bilan) {
            // ---- Véhicules
            $vehicules = [];
            foreach (self::VEHICULES as [$marque, $modele, $plaque, $places, $fabrication]) {
                $id = DB::table('voitures')->where('plaque', $plaque)->value('id');
                if (! $id) {
                    $id = DB::table('voitures')->insertGetId([
                        'marque' => $marque, 'modele' => $modele, 'plaque' => $plaque, 'nombre_place' => $places,
                        'annee_fabrication' => $fabrication, 'kilometrage_actuel' => 0,
                        'statut' => StatutVehicule::DISPONIBLE->value, 'annee_id' => $annee->id, 'etat' => 1,
                        'created_at' => $maintenant, 'updated_at' => $maintenant,
                    ]);
                    $bilan['vehicules']++;
                }
                $vehicules[] = $id;
            }

            // ---- Chauffeurs de test, un par véhicule (les chauffeurs réels ne sont pas touchés)
            $chauffeurs = [];
            foreach (self::CHAUFFEURS_TEST as $i => [$nom, $prenom, $telephone, $permis]) {
                $valeurs = [
                    'nom' => $nom, 'prenom' => $prenom, 'telephone' => $telephone,
                    'email' => strtolower(str_replace(' ', '.', $prenom)) . '@test.local', 'adresse' => 'Lomé (donnée de test)',
                    'date_validite_permis' => $maintenant->copy()->addYears(3)->toDateString(),
                    'voiture_id' => $vehicules[$i % count($vehicules)], 'statut' => 1, 'annee_id' => $annee->id, 'etat' => 1,
                    'updated_at' => $maintenant,
                ];
                $id = DB::table('chauffeurs')->where('permis_conduire', $permis)->value('id');
                if ($id) {
                    DB::table('chauffeurs')->where('id', $id)->update($valeurs);
                } else {
                    $id = DB::table('chauffeurs')->insertGetId($valeurs + ['permis_conduire' => $permis, 'created_at' => $maintenant]);
                    $bilan['chauffeurs']++;
                }
                $chauffeurs[] = $id;
            }

            // ---- Lignes, chacune avec un véhicule et son chauffeur de test
            $lignes = [];
            $i = 0;
            foreach (self::LIGNES as $libelle => [$ancien, $min, $max, $couleur]) {
                $voiture = $vehicules[$i % count($vehicules)];
                $chauffeur = $chauffeurs[$i % count($chauffeurs)];
                $valeurs = [
                    'libelle' => $libelle, 'prix_minimal' => null, 'prix_plafond' => null,
                    'voiture_id' => $voiture, 'chauffeur_id' => $chauffeur,
                    'date_debut' => $annee->date_rentree, 'date_fin' => $annee->date_fin,
                    'etat' => 1, 'updated_at' => $maintenant,
                ];
                $id = DB::table('lignes')->where('annee_id', $annee->id)->whereIn('libelle', [$libelle, $ancien])->value('id');
                if ($id) {
                    DB::table('lignes')->where('id', $id)->update($valeurs);
                } else {
                    $id = DB::table('lignes')->insertGetId($valeurs + ['annee_id' => $annee->id, 'created_at' => $maintenant]);
                    $bilan['lignes']++;
                }
                $lignes[] = ['id' => $id, 'min' => $min, 'max' => $max, 'couleur' => $couleur, 'voiture' => $voiture, 'chauffeur' => $chauffeur];
                $i++;
            }

            // ---- Zones : celles de la base historique (frais de transport), un tarif par zone
            $frais = DB::table('frais_ecoles')->where('etat', 1)->where('type_paiement', TypeDetail::BUS->value)
                ->orderBy('annee_id')->orderBy('libelle')->get(['libelle', 'montant']);
            $vues = [];
            $ordre = 0;
            foreach ($frais as $f) {
                $libelle = trim(preg_replace('/\s+/', ' ', (string) $f->libelle));
                $cle = mb_strtolower(\Illuminate\Support\Str::ascii($libelle));
                if ($libelle === '' || isset($vues[$cle]) || in_array(mb_strtolower($libelle), self::PAS_DES_ZONES, true)) {
                    continue;
                }
                $vues[$cle] = true;
                $ordre++;
                $tarif = (float) $f->montant;
                $ligne = collect($lignes)->first(fn ($l) => $tarif >= $l['min'] && $tarif <= $l['max']) ?? end($lignes);
                $code = sprintf('Z%02d-%d', $ordre, $annee->id);

                $valeurs = [
                    'libelle' => $libelle, 'tarif_base' => $tarif, 'ordre' => $ordre,
                    'couleur' => $ligne['couleur'], 'ligne_id' => $ligne['id'],
                    'voiture_id' => $ligne['voiture'], 'chauffeur_id' => $ligne['chauffeur'],
                    'description' => 'Zone de ramassage (reprise de la base historique)',
                    'annee_id' => $annee->id, 'etat' => 1, 'updated_at' => $maintenant,
                ];
                if (DB::table('zones')->where('code', $code)->exists()) {
                    DB::table('zones')->where('code', $code)->update($valeurs);
                    continue;
                }
                DB::table('zones')->insert($valeurs + ['code' => $code, 'created_at' => $maintenant]);
                $bilan['zones']++;
            }
        });

        $this->command?->info("Transport — année {$annee->libelle} : {$bilan['vehicules']} véhicule(s), {$bilan['chauffeurs']} chauffeur(s) de test, "
            . "{$bilan['lignes']} ligne(s), {$bilan['zones']} zone(s) créés.");
    }
}
