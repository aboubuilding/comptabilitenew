import { test as base, expect, type Page } from '@playwright/test';
import { COMPTES, MOT_DE_PASSE, type Profil } from './comptes';

/**
 * Anomalies relevées pendant un test : exception JavaScript, erreur dans la
 * console, réponse 5xx, ou ressource de l'application introuvable. Elles font
 * échouer le test même si le scénario lui-même est allé au bout.
 */
type Fixtures = {
  anomalies: string[];
  surveillance: void;
};

/** Identifiant de l'année 2026-2027 créée en premier par E2eSeeder (base reconstruite à chaque exécution). */
const ANNEE_E2E = 1;

/** Bruit sans rapport avec l'application (ressources tierces, favicon). */
const IGNORES = [/favicon/i, /fonts\.g(oogleapis|static)\.com/, /apple-touch-icon/];

export const test = base.extend<Fixtures>({
  anomalies: async ({}, use) => {
    await use([]);
  },

  surveillance: [
    async ({ page, anomalies, baseURL }, use) => {
      const origine = new URL(baseURL!).origin;
      const ignore = (texte: string) => IGNORES.some((motif) => motif.test(texte));

      page.on('pageerror', (erreur) => anomalies.push(`Exception JS sur ${page.url()} : ${erreur.message}`));

      page.on('console', (message) => {
        if (message.type() !== 'error') return;
        const texte = message.text();
        // Les échecs de chargement sont déjà rapportés, avec leur URL, par l'écoute des réponses.
        if (texte.startsWith('Failed to load resource') || ignore(texte)) return;
        anomalies.push(`Erreur console sur ${page.url()} : ${texte}`);
      });

      page.on('response', (reponse) => {
        const url = reponse.url();
        if (!url.startsWith(origine) || ignore(url)) return;
        const statut = reponse.status();
        const estRessource = ['script', 'stylesheet', 'image', 'font'].includes(reponse.request().resourceType());
        if (statut >= 500 || (statut === 404 && estRessource)) {
          anomalies.push(`HTTP ${statut} : ${reponse.request().method()} ${url}`);
        }
      });

      await use();

      expect(anomalies, 'Anomalies relevées dans le navigateur').toEqual([]);
    },
    { auto: true },
  ],
});

export { expect };

/**
 * Ouvre une session pour le profil donné, sans passer par le formulaire
 * (le parcours de connexion lui-même est couvert par 00-transversal/connexion.spec.ts).
 */
export async function seConnecter(page: Page, profil: Profil): Promise<void> {
  await page.context().clearCookies();

  const accueil = await page.request.get('/login');
  const jeton = /name="csrf-token" content="([^"]+)"/.exec(await accueil.text())?.[1];
  expect(jeton, 'jeton CSRF de la page de connexion').toBeTruthy();

  const reponse = await page.request.post('/login', {
    headers: { 'X-CSRF-TOKEN': jeton!, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    form: { login: COMPTES[profil], password: MOT_DE_PASSE },
  });
  expect(reponse.status(), `connexion du profil ${profil}`).toBe(200);
  expect((await reponse.json()).success).toBe(true);

  // Année de travail fixée sur celle du jeu de données (2026-2027), pour que
  // les scénarios ne dépendent pas des années créées par d'autres tests.
  await page.request.get(`/tableau?annee_id=${ANNEE_E2E}`);
}

/** Attend le message de confirmation (toast) affiché après une action. */
export async function attendreToast(page: Page, texte?: string | RegExp): Promise<void> {
  const toast = page.locator('#toast-container .toast-item').last();
  await expect(toast).toBeVisible();
  if (texte) await expect(toast).toContainText(texte);
  await expect(toast).not.toHaveClass(/toast-error/);
}

/**
 * Choisit un élève dans le sélecteur commun (liste avec recherche) d'une
 * fenêtre de saisie : on tape quelques lettres, puis on clique sur le résultat.
 */
export async function choisirEleve(page: Page, conteneur: string, recherche: string, resultat?: string): Promise<void> {
  await page.locator(`${conteneur} .select2-selection`).first().click();
  await page.locator('.select2-container--open .select2-search__field').fill(recherche);
  await page.locator('.select2-results__option', { hasText: resultat ?? recherche }).first().click();
}

/**
 * Paiements › « Nouveau paiement » : recherche l'élève dans la liste (nom,
 * prénom ou matricule) et ouvre son écran de saisie.
 */
export async function ouvrirSaisiePaiement(page: Page, recherche: string, resultat?: string): Promise<void> {
  await page.goto('/finances/paiements');
  await page.getByRole('link', { name: /Nouveau paiement/ }).first().click();
  await expect(page.locator('.page-title-main')).toContainText('Nouveau paiement');

  // La liste s'ouvre d'elle-même à l'arrivée sur l'écran : on l'attend plutôt que de
  // cliquer (un clic au même instant la refermerait).
  const champ = page.locator('.select2-container--open .select2-search__field');
  await champ.waitFor({ state: 'visible', timeout: 5_000 }).catch(async () => {
    await page.locator('.recherche-eleve .select2-selection').click();
  });
  await champ.fill(recherche);
  await page.locator('.select2-results__option', { hasText: resultat ?? recherche }).first().click();
  await expect(page.locator('#paiement-form')).toBeVisible();
}

/**
 * Encaissements › onglet « Paiements en attente » : « Encaisser » sur le
 * paiement voulu, contrôle du détail, saisie du montant réellement reçu
 * (par défaut : le montant à encaisser), validation. On revient sur
 * « Mes encaissements du jour ».
 */
export async function encaisserPaiement(page: Page, texte: string | RegExp, montantRecu?: number): Promise<void> {
  await page.goto('/finances/encaissements?onglet=attente');
  const ligne = page.locator('#table-attente tbody tr').filter({ hasText: texte }).first();
  await ligne.locator('.btn-encaisser-action').click();
  const modale = page.locator('#encaisserModal');
  await expect(modale.locator('.resume-paiement')).toBeVisible();
  if (montantRecu !== undefined) await modale.locator('#enc-montant-recu').fill(String(montantRecu));
  await modale.locator('#enc-valider').click();
  await expect(page).toHaveURL(/onglet=jour/);
  await attendreToast(page, 'Encaissement effectué');
}

/** Encaisse, un par un, tous les paiements en attente. */
export async function encaisserTout(page: Page): Promise<void> {
  await page.goto('/finances/encaissements?onglet=attente');
  let restants = await page.locator('#table-attente .btn-encaisser-action').count();
  expect(restants, 'paiements en attente').toBeGreaterThan(0);
  while (restants > 0) {
    await page.locator('#table-attente .btn-encaisser-action').first().click();
    await expect(page.locator('#encaisserModal .resume-paiement')).toBeVisible();
    await page.locator('#enc-valider').click();
    await expect(page).toHaveURL(/onglet=jour/);
    await page.goto('/finances/encaissements?onglet=attente');
    await expect(page.locator('#table-attente .btn-encaisser-action')).toHaveCount(restants - 1);
    restants--;
  }
}

/** Valide la demande de confirmation SweetAlert2 (suppression, désactivation…). */
export async function confirmer(page: Page): Promise<void> {
  await page.locator('.swal2-popup .swal2-confirm').click();
}

/** Ouvre le menu d'actions « … » d'une ligne de tableau et clique sur l'action demandée. */
export async function actionDeLigne(page: Page, ligne: string | RegExp, action: string | RegExp): Promise<void> {
  const rangee = page.locator('tbody tr').filter({ hasText: ligne }).first();
  await rangee.locator('.dropdown-toggle, .btn-action').first().click();
  await rangee.locator('.dropdown-menu .dropdown-item').filter({ hasText: action }).first().click();
}
