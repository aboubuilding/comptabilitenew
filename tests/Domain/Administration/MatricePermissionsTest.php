<?php

namespace Tests\Domain\Administration;

use App\Domain\Administration\DTO\UserDTO;
use App\Domain\Administration\Services\UserService;
use App\Domain\Administration\Types\RoleUtilisateur as Role;
use App\Repositories\BaseRepository;
use App\Support\Permissions;
use Illuminate\Support\Facades\DB;
use Tests\Domain\DomainTestCase;

/**
 * Matrice profil → permissions (config/permissions.php), transcription du
 * cahier des charges : § 3 (profils), § 4 (« Profils concernés » de chaque
 * sous-menu) et § 5 (description par profil).
 */
class MatricePermissionsTest extends DomainTestCase
{
    private function autorise(Role $profil, string $permission): bool
    {
        return app(Permissions::class)->autorise($profil, $permission);
    }

    /**
     * Ce que chaque profil PEUT et NE PEUT PAS faire, d'après le cahier des charges.
     *
     * @return array<string, array{Role, string[], string[]}>
     */
    public function profils(): array
    {
        return [
            // § 5.2 — valide, consulte ; ne saisit ni paiement ni encaissement
            'Direction' => [Role::Directeur,
                ['eleves.voir', 'familles.voir', 'inscriptions.valider', 'frais.voir', 'souscriptions.remise',
                 'caisses.voir', 'depenses.valider', 'analytique.voir', 'recouvrement.voir', 'recouvrement.gerer',
                 'previsions.gerer', 'paie.voir', 'communications.envoyer', 'paiements.voir', 'totaux-encaisses.consulter'],
                ['eleves.gerer', 'inscriptions.saisir', 'frais.gerer', 'paiements.creer', 'encaissements.creer',
                 'caisses.ouvrir', 'depenses.creer', 'analytique.gerer', 'paie.gerer', 'utilisateurs.gerer', 'structure.gerer'],
            ],
            // § 5.3 — enregistre les paiements, n'encaisse pas ; saisit les dépenses, ne les valide pas
            'Comptable' => [Role::Comptable,
                ['inscriptions.voir', 'frais.gerer', 'souscriptions.gerer', 'souscriptions.remise', 'paiements.creer',
                 'paiements.annuler', 'caisses.voir', 'banques.gerer', 'cheques.rapprocher', 'depenses.creer',
                 'depenses.regler', 'analytique.ventiler', 'recouvrement.relancer', 'previsions.gerer', 'achats.voir',
                 'paie.voir', 'paie.payer', 'communications.envoyer'],
                ['encaissements.creer', 'caisses.ouvrir', 'caisses.cloturer', 'depenses.valider', 'inscriptions.valider',
                 'inscriptions.saisir', 'analytique.gerer', 'paie.gerer', 'achats.gerer', 'eleves.gerer', 'utilisateurs.voir'],
            ],
            // § 5.4 — sa caisse et les encaissements, rien d'autre
            'Caissier' => [Role::Caissier,
                ['encaissements.voir', 'encaissements.creer', 'encaissements.annuler', 'caisses.voir', 'caisses.ouvrir',
                 'caisses.cloturer', 'caisses.mouvements'],
                ['paiements.creer', 'paiements.voir', 'depenses.creer', 'frais.voir', 'eleves.voir', 'banques.voir'],
            ],
            // § 5.5 — dossiers élèves, familles, inscriptions ; ne valide pas
            'Secrétariat (code historique)' => [Role::Secretaire,
                ['eleves.gerer', 'familles.gerer', 'inscriptions.voir', 'inscriptions.saisir', 'communications.envoyer'],
                ['inscriptions.valider', 'paiements.voir', 'frais.voir', 'structure.voir', 'modeles.gerer'],
            ],
            'Agent des inscriptions' => [Role::AgentInscriptions,
                ['eleves.gerer', 'familles.gerer', 'inscriptions.saisir', 'communications.envoyer'],
                ['inscriptions.valider', 'paiements.voir'],
            ],
            // § 5.6 — paramètre l'analytique et contrôle les imputations
            'Contrôleur de gestion' => [Role::ControleurGestion,
                ['analytique.voir', 'analytique.gerer', 'analytique.ventiler', 'previsions.voir', 'depenses.voir', 'achats.voir'],
                ['depenses.creer', 'depenses.valider', 'previsions.gerer', 'paiements.voir', 'recouvrement.voir'],
            ],
            // § 5.7
            'Chargé de recouvrement' => [Role::ChargeRecouvrement,
                ['recouvrement.voir', 'recouvrement.gerer', 'recouvrement.relancer', 'communications.envoyer'],
                ['paiements.creer', 'encaissements.creer', 'frais.voir', 'depenses.voir'],
            ],
            // § 5.8
            'Responsable Achats & Stock' => [Role::ResponsableAchatsStock,
                ['fournisseurs.gerer', 'achats.gerer', 'achats.receptionner', 'stock.gerer', 'stock.ajuster'],
                ['ventes.creer', 'depenses.creer', 'cantine.voir'],
            ],
            // § 5.9 — vend et consulte le stock, sans le gérer
            'Responsable Boutique' => [Role::ResponsableBoutique,
                ['ventes.voir', 'ventes.creer', 'ventes.annuler', 'stock.voir'],
                ['stock.gerer', 'stock.ajuster', 'achats.voir', 'fournisseurs.voir'],
            ],
            // § 5.10
            'Responsable Transport' => [Role::ResponsableTransport,
                ['transport.voir', 'transport.gerer', 'transport.abonner'],
                ['cantine.voir', 'stock.voir', 'paiements.voir'],
            ],
            // § 5.11 — compose ses menus depuis le stock, qu'il ne fait que consulter
            'Responsable Cantine' => [Role::ResponsableCantine,
                ['cantine.voir', 'cantine.gerer', 'cantine.inscrire', 'stock.voir'],
                ['stock.gerer', 'transport.voir', 'achats.gerer'],
            ],
            // § 5.12
            'Bibliothécaire' => [Role::Bibliothecaire,
                ['bibliotheque.voir', 'bibliotheque.gerer', 'bibliotheque.preter'],
                ['activites.voir', 'eleves.gerer'],
            ],
            // § 5.13 — prépare la paie ; le règlement revient au Comptable
            'Responsable RH / Paie' => [Role::ResponsableRhPaie,
                ['employes.gerer', 'paie.voir', 'paie.gerer', 'assurances.gerer'],
                ['paie.payer', 'depenses.voir', 'utilisateurs.voir'],
            ],
            // § 5.14
            'Responsable Activités & Événements' => [Role::ResponsableActivitesEvenements,
                ['activites.voir', 'activites.gerer', 'activites.inscrire'],
                ['bibliotheque.voir', 'paiements.creer'],
            ],
        ];
    }

    /** @dataProvider profils */
    public function test_chaque_profil_a_les_droits_que_lui_donne_le_cahier_des_charges(Role $profil, array $permis, array $interdits): void
    {
        foreach ($permis as $permission) {
            $this->assertTrue($this->autorise($profil, $permission), "{$profil->label()} devrait avoir « {$permission} ».");
        }
        foreach ($interdits as $permission) {
            $this->assertFalse($this->autorise($profil, $permission), "{$profil->label()} ne devrait pas avoir « {$permission} ».");
        }
    }

    public function test_l_administrateur_a_un_acces_de_controle_a_tous_les_modules(): void
    {
        foreach (['utilisateurs.gerer', 'structure.gerer', 'journal.voir', 'modeles.gerer', 'paiements.creer', 'paie.payer', 'stock.ajuster'] as $permission) {
            $this->assertTrue($this->autorise(Role::Administrateur, $permission));
        }
    }

    public function test_structure_utilisateurs_referentiels_et_journal_sont_reserves_a_l_administrateur(): void
    {
        $reservees = config('permissions.reservees_administrateur');
        $this->assertContains('utilisateurs.gerer', $reservees);
        $this->assertContains('journal.voir', $reservees);

        foreach (Role::cases() as $profil) {
            if ($profil === Role::Administrateur) {
                continue;
            }
            foreach ($reservees as $permission) {
                $this->assertFalse($this->autorise($profil, $permission), "{$profil->label()} ne doit pas avoir « {$permission} ».");
            }
        }
    }

    public function test_les_profils_hors_cahier_des_charges_n_ont_aucun_droit_de_gestion(): void
    {
        $this->assertTrue($this->autorise(Role::AdministrateurAdjoint, 'paiements.voir'));
        $this->assertFalse($this->autorise(Role::AdministrateurAdjoint, 'paiements.creer'));
        $this->assertFalse($this->autorise(Role::AdministrateurAdjoint, 'utilisateurs.voir'));
        $this->assertSame([], app(Permissions::class)->pour(Role::Enseignant));
        $this->assertFalse($this->autorise(Role::Enseignant, 'eleves.voir'));
    }

    public function test_un_utilisateur_sans_profil_n_a_aucun_droit(): void
    {
        $this->assertFalse(app(Permissions::class)->autorise(null, 'eleves.voir'));
    }

    public function test_un_compte_desactive_perd_ses_droits(): void
    {
        $comptable = $this->creerUtilisateur(Role::Comptable, ['etat' => BaseRepository::INACTIF]);

        $this->assertFalse($comptable->can('paiements.creer'));
    }

    /* ---------- Cumul de profils (§ 3) ---------- */

    public function test_un_utilisateur_qui_cumule_deux_profils_a_les_droits_des_deux(): void
    {
        $comptable = $this->creerUtilisateur(Role::Comptable);
        $this->assertFalse($comptable->can('encaissements.creer'));

        $comptable->profilsComplementaires()->attach(Role::Caissier->value);
        $comptable->refresh();

        $this->assertTrue($comptable->can('encaissements.creer'), 'Le profil Caissier ajouté donne le droit d\'encaisser.');
        $this->assertTrue($comptable->can('paiements.creer'), 'Le profil principal est conservé.');
        $this->assertFalse($comptable->can('depenses.valider'), 'Aucun des deux profils ne valide les dépenses.');
        $this->assertSame([Role::Comptable, Role::Caissier], $comptable->profils());
    }

    public function test_l_ecran_utilisateurs_enregistre_le_profil_et_les_profils_complementaires(): void
    {
        $user = app(UserService::class)->creer(UserDTO::fromArray([
            'nom' => 'AGBEKO', 'prenom' => 'Sena', 'login' => 'sagbeko', 'mot_passe' => 'Secret-2026',
            'role_id' => Role::Comptable->value,
            'profils_complementaires' => [Role::ChargeRecouvrement->value, Role::Comptable->value],
        ]))->fresh();

        $this->assertSame(Role::Comptable, $user->role);
        $this->assertSame(Role::Comptable->value, (int) $user->role_id);
        $this->assertSame([Role::Comptable, Role::ChargeRecouvrement], $user->profils());
        $this->assertSame(1, DB::table('role_user')->where('user_id', $user->id)->count(), 'Le profil principal n\'est pas répété.');
    }

    public function test_le_seeder_cree_les_comptes_des_profils_sans_utilisateur_et_peut_etre_relance(): void
    {
        $this->seed(\Database\Seeders\ComptesProfilsSeeder::class);
        $this->seed(\Database\Seeders\ComptesProfilsSeeder::class);

        $caissier = \App\Domain\Administration\Models\User::where('login', 'caissier')->sole();
        $this->assertSame(Role::Caissier, $caissier->role);
        $this->assertTrue($caissier->isActive());
        $this->assertTrue($caissier->can('encaissements.creer'));
        $this->assertFalse($caissier->can('caisses.valider_ecart'), 'Le caissier ne valide pas ses propres écarts.');

        $this->assertSame(Role::AdministrateurAdjoint, \App\Domain\Administration\Models\User::where('login', 'adjoint')->sole()->role);
        $this->assertSame(Role::Enseignant, \App\Domain\Administration\Models\User::where('login', 'enseignant')->sole()->role);
        $this->assertNotSame('', (string) $caissier->mot_passe);
    }

    public function test_le_referentiel_des_profils_reprend_les_codes_de_l_enumeration(): void
    {
        foreach (Role::cases() as $profil) {
            $this->assertSame(
                $profil->label(),
                DB::table('roles')->where('id', $profil->value)->value('libelle'),
                "Le profil {$profil->name} doit exister dans la table roles avec l'identifiant {$profil->value}."
            );
        }
    }

    /* ---------- Cohérence entre le code et la matrice ---------- */

    public function test_toute_permission_utilisee_dans_le_code_est_connue_de_la_matrice(): void
    {
        $utilisees = [];
        $dossiers = [app_path(), resource_path('views'), base_path('routes'), config_path('menu.php')];

        foreach ($dossiers as $dossier) {
            $fichiers = is_file($dossier)
                ? [new \SplFileInfo($dossier)]
                : new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dossier, \FilesystemIterator::SKIP_DOTS));

            foreach ($fichiers as $fichier) {
                if ($fichier->getExtension() !== 'php') {
                    continue;
                }
                $source = file_get_contents($fichier->getPathname());
                preg_match_all("/permission:([a-z_.,]+)'/", $source, $middleware);
                preg_match_all("/(?:can|canany|cannot)\(\[?'([a-z_]+\.[a-z_]+)'/i", $source, $gates);
                preg_match_all("/'([a-z_]+\.(?:voir|gerer|creer|annuler|valider|saisir|payer|remise|envoyer|ouvrir|cloturer|mouvements|regler|rapprocher|ventiler|relancer|receptionner|ajuster|abonner|inscrire|preter))'\]/", $source, $menu);

                foreach ($middleware[1] as $liste) {
                    array_push($utilisees, ...explode(',', $liste));
                }
                array_push($utilisees, ...$gates[1], ...$menu[1]);
            }
        }

        $utilisees = array_unique($utilisees);
        $this->assertGreaterThan(50, count($utilisees));

        $reservees = config('permissions.reservees_administrateur');
        $permissions = app(Permissions::class);

        foreach ($utilisees as $permission) {
            $accordee = in_array($permission, $reservees, true);

            foreach (Role::cases() as $profil) {
                if ($profil !== Role::Administrateur && $permissions->autorise($profil, $permission)) {
                    $accordee = true;
                    break;
                }
            }

            $this->assertTrue($accordee, "La permission « {$permission} » est utilisée dans le code mais aucun profil ne la reçoit (faute de frappe ou oubli dans config/permissions.php).");
        }
    }

    public function test_aucune_route_applicative_n_echappe_au_controle_des_permissions(): void
    {
        // Seules ces routes sont ouvertes à tout utilisateur (connecté ou non, selon le cas)
        // profil : chacun agit sur son propre compte ; recherche : chaque source est
        // filtrée par les permissions de l'utilisateur (RechercheController).
        $libres = ['/', 'login', 'logout', 'tableau', 'api/user', 'profil', 'profil/mot-de-passe', 'recherche'];
        $router = app('router');
        $sansControle = [];

        foreach ($router->getRoutes() as $route) {
            $uri = $route->uri();
            if (in_array($uri, $libres, true) || str_starts_with($uri, '_ignition') || str_starts_with($uri, 'sanctum')) {
                continue;
            }

            $protegee = collect($router->gatherRouteMiddleware($route))
                ->contains(fn ($m) => is_string($m) && str_contains($m, 'CheckPermission'));

            if (! $protegee) {
                $sansControle[] = implode('|', $route->methods()) . ' ' . $uri;
            }
        }

        $this->assertSame([], $sansControle, 'Ces routes ne déclarent aucune permission : ' . implode(', ', $sansControle));
    }

    /* ---------- Application réelle : pages et menu ---------- */

    /** @return array<string, array{Role, string[], string[]}> */
    public function pages(): array
    {
        return [
            'Caissier' => [Role::Caissier,
                ['/finances/encaissements', '/finances/caisses'],
                ['/finances/paiements', '/finances/depenses', '/scolarite/eleves', '/administration/users'],
            ],
            'Comptable' => [Role::Comptable,
                ['/finances/paiements', '/finances/frais-ecoles', '/finances/depenses', '/scolarite/inscriptions'],
                ['/finances/encaissements', '/scolarite/eleves', '/rh/employes', '/administration/users'],
            ],
            'Secrétariat' => [Role::Secretaire,
                ['/scolarite/eleves', '/scolarite/espaces', '/scolarite/inscriptions', '/administration/messages'],
                ['/finances/paiements', '/scolarite/annees', '/administration/modeles', '/administration/journal'],
            ],
            'Direction' => [Role::Directeur,
                ['/scolarite/eleves', '/finances/depenses', '/finances/recouvrement', '/rh/paies', '/finances/paiements'],
                ['/finances/paiements/nouveau', '/finances/encaissements', '/administration/users', '/scolarite/cycles'],
            ],
            'Responsable Transport' => [Role::ResponsableTransport,
                ['/services-eleves/transport/voitures', '/services-eleves/transport/lignes'],
                ['/services-eleves/cantine/menus', '/logistique/produits'],
            ],
        ];
    }

    /** @dataProvider pages */
    public function test_les_pages_s_ouvrent_ou_se_ferment_selon_le_profil(Role $profil, array $ouvertes, array $fermees): void
    {
        $this->actingAs($this->creerUtilisateur($profil));

        foreach ($ouvertes as $page) {
            $this->get($page)->assertOk();
        }
        foreach ($fermees as $page) {
            $this->get($page)->assertForbidden();
        }
    }

    public function test_tout_utilisateur_connecte_accede_au_tableau_de_bord(): void
    {
        $this->actingAs($this->creerUtilisateur(Role::Enseignant));

        $this->get('/tableau')->assertOk();
    }

    public function test_le_menu_ne_montre_que_les_modules_du_profil(): void
    {
        $this->actingAs($this->creerUtilisateur(Role::Caissier));

        $this->get('/tableau')
            ->assertOk()
            ->assertSee('Encaissements')
            ->assertSee(route('finances.caisses.index'))
            ->assertDontSee(route('finances.paiements.index'))
            ->assertDontSee(route('scolarite.eleves.index'))
            ->assertDontSee(route('administration.users.index'));
    }

    public function test_un_formulaire_refuse_l_action_d_un_profil_non_autorise(): void
    {
        // Le Comptable saisit les dépenses mais ne les valide pas (2.6)
        $comptable = $this->creerUtilisateur(Role::Comptable);
        $depenseId = DB::table('depenses')->insertGetId([
            'libelle' => 'Fournitures', 'montant' => 25000, 'date_depense' => '2026-09-20',
            'statut_depense' => 1, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);

        $this->actingAs($comptable)->post("/finances/depenses/{$depenseId}/valider")->assertForbidden();
        $this->assertSame(1, (int) DB::table('depenses')->where('id', $depenseId)->value('statut_depense'));
    }
}
