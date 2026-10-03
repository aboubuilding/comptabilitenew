# Tests de bout en bout (Playwright)

Ces tests pilotent l'application dans un vrai navigateur (Chromium), comme le
ferait un utilisateur : connexion, menus, fenêtres de saisie, messages de
confirmation. Ils sont organisés comme le cahier des charges
(`Cahier_des_charges_gestion_scolaire_v9_domaines.docx`).

## Lancer les tests

```bash
npm run e2e                       # toute la suite (≈ 30 min)
npm run e2e -- e2e/02-finances    # un menu
npm run e2e -- -g "Recouvrement"  # les scénarios dont le titre contient ce mot
npm run e2e:rapport               # ouvrir le rapport HTML de la dernière exécution
```

Prérequis : MySQL (Laragon) démarré, `npm install`, puis une seule fois
`npx playwright install chromium`.

## Ce que fait une exécution

1. **Base dédiée** — `comptabilite_e2e` est recréée par les migrations puis
   remplie par `database/seeders/E2eSeeder.php` (une année, une structure
   pédagogique, une famille et deux élèves, des frais, un stock de départ, un
   employé, et **un compte par profil**). La base de travail n'est jamais
   touchée : le seeder et la préparation refusent toute base dont le nom ne
   finit pas par `_e2e`.
2. **Serveur dédié** — un serveur PHP est lancé sur `127.0.0.1:8123` avec
   cette base, et `MAIL_MAILER=log` : aucun email réel ne part.
3. **Surveillance** — chaque test échoue s'il relève une exception
   JavaScript, une erreur dans la console, une réponse HTTP 5xx ou une
   ressource introuvable, même si le scénario est allé au bout
   (`support/test.ts`).

## Organisation

| Dossier | Cahier des charges |
|---|---|
| `00-transversal/` | § 3 profils et connexion, matrice des droits (menu et accès de chaque profil), consultation seule, tableau de bord, profil, recherche |
| `01-scolarite/` | Menu 1 — élèves, parents & familles, inscriptions, structure pédagogique |
| `02-finances/` | Menu 2 — frais & échéanciers, écran « Nouveau paiement » (recherche de l'élève, chèque, liste à 5 colonnes, détail, totaux encaissés, souscriptions cantine/transport/activité/événement depuis l'écran), écran Encaissements à onglets (nouvel encaissement, montant reçu et monnaie, reçu, annulations), circuit du paiement (souscription → paiement → encaissement → caisse → écart), banques & chèques, dépenses, analytique, recouvrement, prévisions |
| `03-logistique/` | Menu 3 — fournisseurs, achats, produits & stock, boutique / point de vente |
| `04-services-eleves/` | Menu 4 — transport, cantine, bibliothèque, activités & événements |
| `05-ressources-humaines/` | Menu 5 — employés, paie, assurances du personnel |
| `06-administration/` | Menu 6 — utilisateurs & profils (dont le cumul), communications, journal |

Les règles transversales du § 6 sont vérifiées là où elles s'appliquent :
cycle de vie d'une inscription (`01-scolarite/inscriptions`), cycle de vie
d'un paiement, journal de caisse et écarts (`02-finances/circuit-paiement`),
circuit de validation des dépenses et ventilation analytique
(`02-finances/depenses`), souscription indépendante du paiement
(`04-services-eleves/transport`, `cantine`), trois régimes d'engagement
(`04-services-eleves/activites-evenements`), décrément de stock commun au
panier et au point de vente (`02-finances/circuit-paiement`,
`03-logistique/boutique`).

## Comptes de test

Un compte par profil, créé par le seeder : `e2e.admin`, `e2e.directeur`,
`e2e.comptable`, `e2e.caissier`, `e2e.secretaire`, `e2e.controleur`,
`e2e.recouvrement`, `e2e.achats`, `e2e.boutique`, `e2e.transport`,
`e2e.cantine`, `e2e.biblio`, `e2e.rh`, `e2e.activites`, `e2e.adjoint`,
`e2e.enseignant` (`support/comptes.ts`). Ils n'existent que dans la base E2E.

## Écrire un scénario

```ts
import { attendreToast, expect, seConnecter, test } from '../support/test';

test('le Comptable crée une banque', async ({ page }) => {
  await seConnecter(page, 'comptable');        // session ouverte sans passer par le formulaire
  await page.goto('/finances/banques');
  await page.locator('#btnNouvelleBanque').click();
  await page.locator('#banque-nom').fill('ORABANK');
  await page.locator('#banque-form button[type=submit]').click();
  await attendreToast(page);                   // message de confirmation, et pas un message d'erreur
  await expect(page.locator('tbody')).toContainText('ORABANK');
});
```

Autres aides de `support/test.ts` : `confirmer` (fenêtre de confirmation),
`actionDeLigne` (menu « … » d'une ligne), `choisirEleve` (sélecteur d'élèves
avec recherche).

Les tests s'exécutent **un par un** (le serveur PHP intégré traite une
requête à la fois) et partagent la base d'une exécution : dans un même
fichier, les scénarios qui se suivent sont déclarés en série
(`test.describe.configure({ mode: 'serial' })`) et chaque fichier travaille
sur ses propres données (un produit, un élève, une caisse qui lui sont
propres) pour ne pas dépendre de l'ordre des autres fichiers.

## En cas d'échec

Le rapport (`npm run e2e:rapport`) contient pour chaque échec une capture
d'écran et une trace rejouable (`npx playwright show-trace <trace.zip>`).
Les erreurs côté serveur sont dans `storage/logs/laravel.log`.
