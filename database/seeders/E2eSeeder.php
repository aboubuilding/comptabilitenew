<?php

namespace Database\Seeders;

use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypeForfait;
use App\Domain\Finances\Types\TypePaiement;
use App\Domain\Logistique\Services\StockService;
use App\Domain\Logistique\Types\TypeMagasin;
use App\Domain\Scolarite\Types\StatutAnnee;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Support\AnneeScolaireContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Jeu de données de départ des tests de bout en bout (Playwright, dossier e2e/).
 *
 * N'est jamais joué sur la base de travail : il refuse de s'exécuter si la
 * base ne s'appelle pas *_e2e. Les comptes créés ici ont un mot de passe
 * connu (voir e2e/support/comptes.ts) : ils ne doivent exister nulle part ailleurs.
 */
class E2eSeeder extends Seeder
{
    public const MOT_DE_PASSE = 'E2e-Secret-1';

    /** login => profil */
    public const COMPTES = [
        'e2e.admin'        => RoleUtilisateur::Administrateur,
        'e2e.directeur'    => RoleUtilisateur::Directeur,
        'e2e.comptable'    => RoleUtilisateur::Comptable,
        'e2e.caissier'     => RoleUtilisateur::Caissier,
        'e2e.secretaire'   => RoleUtilisateur::Secretaire,
        'e2e.controleur'   => RoleUtilisateur::ControleurGestion,
        'e2e.recouvrement' => RoleUtilisateur::ChargeRecouvrement,
        'e2e.achats'       => RoleUtilisateur::ResponsableAchatsStock,
        'e2e.boutique'     => RoleUtilisateur::ResponsableBoutique,
        'e2e.transport'    => RoleUtilisateur::ResponsableTransport,
        'e2e.cantine'      => RoleUtilisateur::ResponsableCantine,
        'e2e.biblio'       => RoleUtilisateur::Bibliothecaire,
        'e2e.rh'           => RoleUtilisateur::ResponsableRhPaie,
        'e2e.activites'    => RoleUtilisateur::ResponsableActivitesEvenements,
        'e2e.adjoint'      => RoleUtilisateur::AdministrateurAdjoint,
        'e2e.enseignant'   => RoleUtilisateur::Enseignant,
    ];

    public function run(): void
    {
        $base = (string) DB::connection()->getDatabaseName();
        if (! str_ends_with($base, '_e2e')) {
            throw new RuntimeException("E2eSeeder refusé : la base « {$base} » n'est pas une base *_e2e.");
        }

        $maintenant = now();

        /* ---------- Année scolaire et structure pédagogique ---------- */
        $anneeId = DB::table('annees')->insertGetId([
            'libelle'                      => '2026-2027',
            'date_rentree'                 => '2026-09-15',
            'date_fin'                     => '2027-07-15',
            'date_ouverture_inscription'   => '2026-06-01',
            'date_fermeture_reinscription' => '2027-01-31',
            'statut_annee'                 => StatutAnnee::Ouvert->value,
            'etat'                         => 1,
            'created_at'                   => $maintenant,
            'updated_at'                   => $maintenant,
        ]);
        app(AnneeScolaireContext::class)->set($anneeId);

        $cycleId = DB::table('cycles')->insertGetId(['libelle' => 'Primaire', 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);

        $niveaux = [];
        foreach (['CP' => 1, 'CE1' => 2] as $libelle => $ordre) {
            $niveaux[$libelle] = DB::table('niveaux')->insertGetId([
                'libelle' => $libelle, 'numero_ordre' => $ordre, 'cycle_id' => $cycleId, 'etat' => 1,
                'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
        }

        $classes = [];
        foreach ($niveaux as $libelle => $niveauId) {
            $classes[$libelle] = DB::table('classes')->insertGetId([
                'libelle' => "{$libelle} A", 'cycle_id' => $cycleId, 'niveau_id' => $niveauId, 'annee_id' => $anneeId, 'etat' => 1,
                'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
        }

        $nationaliteId = DB::table('nationalites')->insertGetId(['libelle' => 'Togolaise', 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);

        /* ---------- Un compte par profil ---------- */
        foreach (self::COMPTES as $login => $profil) {
            DB::table('users')->insert([
                'nom'        => 'E2E',
                'prenom'     => $profil->label(),
                'login'      => $login,
                'email'      => "{$login}@ecole.test",
                'mot_passe'  => bcrypt(self::MOT_DE_PASSE),
                'role'       => $profil->value,
                'role_id'    => $profil->value,
                'etat'       => 1,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }

        /* ---------- Une famille, deux élèves inscrits et validés en CP ---------- */
        $espaceId = DB::table('espaces')->insertGetId(['nom_famille' => 'Famille KOFFI', 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);
        DB::table('parent_eleves')->insert([
            'nom_parent' => 'KOFFI', 'prenom_parent' => 'Yao', 'telephone' => '90000001', 'email' => 'yao.koffi@exemple.test',
            'espace_id' => $espaceId, 'is_principal' => 1, 'annee_id' => $anneeId, 'etat' => 1,
            'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);

        // La scolarité est portée par l'inscription : 469 000, avec 20 % de remise pour Ama
        foreach ([['KOFFI', 'Ama', 2, 'E2E0001', 20], ['KOFFI', 'Kossi', 1, 'E2E0002', 0]] as [$nom, $prenom, $sexe, $matricule, $remise]) {
            $eleveId = DB::table('eleves')->insertGetId([
                'matricule' => $matricule, 'nom' => $nom, 'prenom' => $prenom, 'sexe' => $sexe,
                'date_naissance' => '2019-03-12', 'nationalite_id' => $nationaliteId, 'espace_id' => $espaceId, 'etat' => 1,
                'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
            DB::table('inscriptions')->insert([
                'eleve_id' => $eleveId, 'annee_id' => $anneeId, 'cycle_id' => $cycleId, 'niveau_id' => $niveaux['CP'],
                'classe_id' => $classes['CP'], 'espace_id' => $espaceId,
                // Inscrits depuis 45 jours : au-delà du seuil d'ouverture d'un dossier de recouvrement
                'date_inscription' => now()->subDays(45)->toDateString(),
                'type_inscription' => 1, 'statut_validation' => StatutValidationInscription::VALIDEE->value, 'statut_abandon' => 0,
                'frais_scolarite' => 469000, 'taux_remise' => $remise,
                'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
        }

        /* ---------- Un élève tel que la base historique les enregistre ----------
           Son dû est porté par l'inscription (scolarité avec 5 % de remise,
           inscription, assurance, cantine) ; il a déjà versé une tranche. */
        $senaId = DB::table('eleves')->insertGetId([
            'matricule' => 'E2E0003', 'nom' => 'ADJO', 'prenom' => 'Sena', 'sexe' => 2,
            'date_naissance' => '2018-06-01', 'nationalite_id' => $nationaliteId, 'etat' => 1,
            'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);
        $senaInscriptionId = DB::table('inscriptions')->insertGetId([
            'eleve_id' => $senaId, 'annee_id' => $anneeId, 'cycle_id' => $cycleId, 'niveau_id' => $niveaux['CE1'],
            'classe_id' => $classes['CE1'], 'date_inscription' => now()->toDateString(),
            'type_inscription' => 1, 'statut_validation' => StatutValidationInscription::VALIDEE->value, 'statut_abandon' => 0,
            'frais_scolarite' => 480000, 'taux_remise' => 5, 'frais_inscription' => 50000,
            'frais_assurance' => 10000, 'frais_cantine' => 108000,
            'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);
        $paiementId = DB::table('paiements')->insertGetId([
            'reference' => 'PAI-HIST-0001', 'montant' => 210000, 'payeur' => 'ADJO Kokou',
            'date_paiement' => '2026-09-20', 'statut_paiement' => 2, 'mode_paiement' => 1,
            'inscription_id' => $senaInscriptionId, 'annee_id' => $anneeId, 'etat' => 1,
            'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);
        foreach ([[200000, TypeDetail::FRAIS_SCOLAIRE, 'Scolarité 1re tranche'], [10000, TypeDetail::FRAIS_ASSURANCE, 'Assurance']] as [$montant, $type, $libelle]) {
            DB::table('details')->insert([
                'paiement_id' => $paiementId, 'inscription_id' => $senaInscriptionId, 'annee_id' => $anneeId,
                'libelle' => $libelle, 'montant' => $montant, 'type_paiement' => $type->value, 'statut_paiement' => 2,
                'date_paiement' => '2026-09-20', 'date_encaissement' => '2026-09-20', 'etat' => 1,
                'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
        }

        /* ---------- Un élève qui ne doit que sa scolarité ----------
           Sert aux souscriptions faites depuis l'écran de paiement (cantine,
           transport, activité, événement) : aucun service au départ. */
        $yaoId = DB::table('eleves')->insertGetId([
            'matricule' => 'E2E0004', 'nom' => 'SAMLAN', 'prenom' => 'Yao', 'sexe' => 1,
            'date_naissance' => '2018-02-10', 'nationalite_id' => $nationaliteId, 'etat' => 1,
            'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);
        DB::table('inscriptions')->insert([
            'eleve_id' => $yaoId, 'annee_id' => $anneeId, 'cycle_id' => $cycleId, 'niveau_id' => $niveaux['CE1'],
            'classe_id' => $classes['CE1'], 'date_inscription' => now()->toDateString(),
            'type_inscription' => 1, 'statut_validation' => StatutValidationInscription::VALIDEE->value, 'statut_abandon' => 0,
            'frais_scolarite' => 300000,
            'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);

        // Ce que l'on peut lui souscrire
        $ligneKegue = DB::table('lignes')->insertGetId(['libelle' => 'Ligne Kégué-Centre', 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);
        DB::table('zones')->insert(['libelle' => 'Zone Kégué', 'tarif_base' => 12000, 'ligne_id' => $ligneKegue, 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);
        DB::table('activites')->insert([
            'libelle' => 'Taekwondo', 'montant' => 30000, 'type' => 'sportive', 'encadreur' => 'M. Agbeko',
            'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);
        DB::table('evenements')->insert([
            ['nom' => 'Visite du musée', 'type' => 'sortie_pedagogique', 'date_evenement' => now()->addDays(30)->toDateString(),
             'participation' => 8000, 'capacite' => 30, 'capacite_limitee' => 1, 'date_limite_confirmation' => now()->addDays(20)->toDateString(),
             'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant],
            ['nom' => 'Fête de l\'école', 'type' => 'autre', 'date_evenement' => now()->addDays(40)->toDateString(),
             'participation' => 2500, 'capacite' => null, 'capacite_limitee' => 0, 'date_limite_confirmation' => null,
             'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant],
        ]);

        /* ---------- Finances : frais, banque, centre analytique ---------- */
        DB::table('frais_ecoles')->insert([
            'libelle' => 'Cantine — déjeuner', 'montant' => 15000, 'type_paiement' => TypeDetail::CANTINE->value,
            'modalite_paiement' => TypePaiement::COMPTANT->value, 'type_forfait' => TypeForfait::OPTIONNEL->value,
            'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);
        DB::table('frais_ecoles')->insert([
            ['libelle' => 'Scolarité CP', 'montant' => 469000, 'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value,
             'modalite_paiement' => TypePaiement::COMPTANT->value, 'type_forfait' => TypeForfait::OBLIGATOIRE->value,
             'niveau_id' => $niveaux['CP'], 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant],
            ['libelle' => 'Cantine CP', 'montant' => 108000, 'type_paiement' => TypeDetail::CANTINE->value,
             'modalite_paiement' => TypePaiement::COMPTANT->value, 'type_forfait' => TypeForfait::OPTIONNEL->value,
             'niveau_id' => $niveaux['CP'], 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant],
        ]);
        // Articles vendus comme « frais » (catalogue de la base historique)
        DB::table('frais_ecoles')->insert([
            ['libelle' => 'TENUE_DE_SPORT', 'montant' => 7500, 'type_paiement' => TypeDetail::PRODUIT->value,
             'modalite_paiement' => TypePaiement::COMPTANT->value, 'type_forfait' => TypeForfait::OPTIONNEL->value,
             'niveau_id' => null, 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant],
            ['libelle' => 'LIVRES_CE1', 'montant' => 25000, 'type_paiement' => TypeDetail::LIVRE->value,
             'modalite_paiement' => TypePaiement::COMPTANT->value, 'type_forfait' => TypeForfait::OPTIONNEL->value,
             'niveau_id' => $niveaux['CE1'], 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant],
        ]);
        // Échéancier de la scolarité du CP : T1 échue depuis 30 jours, T2 à venir (dossiers de recouvrement)
        app(\App\Domain\Finances\Services\EcheancierService::class)->definirPourFrais(
            \App\Domain\Finances\Models\FraisEcole::where('libelle', 'Scolarité CP')->where('annee_id', $anneeId)->firstOrFail(),
            [
                ['libelle' => 'T1', 'date_echeance' => now()->subDays(30)->toDateString(), 'montant' => 200000],
                ['libelle' => 'T2', 'date_echeance' => now()->addDays(90)->toDateString(), 'montant' => 269000],
            ]
        );
        DB::table('banques')->insert(['nom' => 'ECOBANK', 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);
        DB::table('centres_analytiques')->insert([
            'code' => 'CANT', 'libelle' => 'Cantine', 'type' => 'service', 'annee_id' => $anneeId, 'etat' => 1,
            'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);

        /* ---------- Logistique : magasins, produits, stock de départ ---------- */
        $entrepotId = DB::table('magasins')->insertGetId(['libelle' => 'Entrepôt central', 'type' => TypeMagasin::ENTREPOT->value, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);
        $boutiqueId = DB::table('magasins')->insertGetId(['libelle' => 'Boutique scolaire', 'type' => TypeMagasin::BOUTIQUE->value, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);

        $tshirtId = DB::table('produits')->insertGetId(['code' => 'TSHIRT-M', 'libelle' => 'T-shirt M', 'prix_unitaire' => 6500, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);
        $rizId    = DB::table('produits')->insertGetId(['code' => 'RIZ-KG', 'libelle' => 'Riz (kg)', 'prix_unitaire' => 600, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);

        $stock = app(StockService::class);
        $stock->entrerEnStock($tshirtId, $boutiqueId, 20, 4000, 'Stock initial E2E');
        $stock->entrerEnStock($rizId, $entrepotId, 100, 600, 'Stock initial E2E');

        DB::table('fournisseurs')->insert(['raison_social' => 'Papeterie du Centre', 'telephone_contact' => '90111111', 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);

        /* ---------- RH : profession, employé, période de paie ---------- */
        $professionId = DB::table('professions')->insertGetId(['libelle' => 'Enseignant du primaire', 'salaire_base' => 150000, 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);
        DB::table('employes')->insert([
            'nom_prenom' => 'AMOUZOU Kossi', 'telephone' => '90444444', 'salaire' => 150000, 'profession_id' => $professionId,
            'mode_paiement' => 'billette', 'irpp_mode' => 'auto', 'nbre_enfant' => 0, 'annee_id' => $anneeId, 'etat' => 1,
            'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);
        DB::table('periodes')->insert(['libelle' => 'Septembre 2026', 'date_debut' => '2026-09-01', 'date_fin' => '2026-09-30', 'annee_id' => $anneeId, 'etat' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant]);
    }
}
