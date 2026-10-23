import type { Page } from '@playwright/test';
import { attendreToast, expect, ouvrirSaisiePaiement, seConnecter, test } from '../support/test';

/**
 * Menu 2 › 2.3 Encaissements — l'écran à onglets du caissier.
 *
 *  - « Caisse ouverte » : « Nouvel encaissement » ouvre une fenêtre où
 *    l'on choisit le paiement en attente par le nom de l'élève ; son détail
 *    s'affiche, le caissier saisit le montant réellement reçu et valide. Le reçu
 *    s'imprime ensuite à tout moment.
 *  - « Tous les encaissements » : la caisse et le caissier de chaque encaissement,
 *    et son détail.
 *  - « Paiements en attente » : seul l'Administrateur peut annuler un paiement.
 *
 * L'élève SAMLAN Yao (E2E0004) reçoit deux paiements de scolarité (20 000 et 10 000).
 */
test.describe.configure({ mode: 'serial' });

const MATRICULE = 'E2E0004';
const ELEVE = 'SAMLAN Yao';

async function saisirPaiement(page: Page, montant: number, payeur: string): Promise<void> {
  await ouvrirSaisiePaiement(page, MATRICULE);
  await page.locator('.engagement-row.exists').filter({ hasText: 'Frais de scolarité' }).locator('.engagement-saisie').fill(String(montant));
  await page.locator('#payeur').fill(payeur);
  await page.locator('#btn-submit').click();
  await expect(page).toHaveURL(/\/finances\/paiements$/);
  await attendreToast(page, 'Paiement enregistré');
}

async function ouvrirCaisseSiBesoin(page: Page): Promise<void> {
  await page.goto('/finances/caisses');
  if ((await page.locator('tbody tr').filter({ hasText: 'Ouverte' }).count()) > 0) return;

  await page.locator('[data-bs-target="#caisseModal"]').click();
  const modale = page.locator('#caisseModal');
  await modale.locator('[name=libelle]').fill('Caisse du guichet');
  await modale.locator('[name=solde_initial]').fill('0');
  await modale.locator('button[type=submit]').click();
  await attendreToast(page);
}

test.describe('2.3 Encaissements — écran à onglets', () => {
  test('le Comptable saisit deux paiements, qui attendent l\'encaissement', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await saisirPaiement(page, 20000, 'SAMLAN Afi');
    await saisirPaiement(page, 10000, 'SAMLAN Komi');
  });

  test('« Nouvel encaissement » : choisir le paiement par le nom de l\'élève, saisir le montant reçu, valider', async ({ page }) => {
    await seConnecter(page, 'caissier');
    await ouvrirCaisseSiBesoin(page);
    await page.goto('/finances/encaissements');

    // Quatre onglets ; le caissier arrive sur les encaissements de sa caisse ouverte, d'où s'ouvre son journal
    await expect(page.locator('#onglet-tous')).toContainText('Tous les encaissements');
    await expect(page.locator('#onglet-attente')).toContainText('Paiements en attente');
    await expect(page.locator('#onglet-mes')).toContainText('Mes encaissements');
    await expect(page.locator('#onglet-caisse')).toHaveClass(/active/);
    await expect(page.locator('#btn-journal-caisse')).toHaveAttribute('href', /\/finances\/caisses\/\d+$/);
    await expect(page.getByRole('link', { name: /Ouvrir une caisse/ })).toHaveCount(0);

    // « Nouvel encaissement » ouvre un écran dédié
    await page.locator('#btn-nouvel-encaissement').click();
    await expect(page).toHaveURL(/\/finances\/encaissements\/nouveau$/);
    await expect(page.locator('.page-title-main')).toContainText('Nouvel encaissement');
    const modale = page.locator('#ecran-encaissement');
    await expect(modale.locator('#caisse-ouverte')).toBeVisible();
    await expect(page.locator('#enc-valider')).toBeDisabled();

    const champ = page.locator('.select2-container--open .select2-search__field');
    await champ.waitFor({ state: 'visible', timeout: 5_000 }).catch(async () => {
      await modale.locator('.select2-selection').click();
    });
    await champ.fill('samlan');
    const options = page.locator('.select2-results__option', { hasText: ELEVE });
    await expect(options).toHaveCount(2);
    await options.filter({ hasText: /20\s000/ }).click();

    // Le détail du paiement s'affiche sous la liste
    await expect(modale.locator('.resume-paiement')).toContainText(ELEVE);
    await expect(modale.locator('.resume-paiement')).toContainText('SAMLAN Afi');
    await expect(modale.locator('.resume-lignes tbody tr')).toHaveCount(1);
    await expect(modale.locator('#enc-montant-recu')).toHaveValue('20000');
    await expect(modale.locator('#enc-ecart')).toContainText('Montant exact');
    await page.locator('#enc-valider').click();

    // Succès : notification et retour sur « Caisse ouverte »
    await expect(page).toHaveURL(/\/finances\/encaissements\?onglet=caisse$/);
    await attendreToast(page, 'Encaissement effectué');
    await expect(page.locator('#onglet-caisse')).toHaveClass(/active/);

    const ligne = page.locator('#table-caisse tbody tr').filter({ hasText: ELEVE });
    await expect(ligne).toHaveCount(1);
    await expect(ligne).toContainText(/20\s000/);
    await expect(ligne).toContainText('E2E Caissier');

    // Le reçu s'imprime à tout moment
    const pdf = await page.request.get((await ligne.locator('.btn-imprimer-recu').getAttribute('href'))!);
    expect(pdf.status()).toBe(200);
    expect(pdf.headers()['content-type']).toContain('pdf');
  });

  test('« Tous les encaissements » : caisse, caissier et détail de chaque encaissement', async ({ page }) => {
    await seConnecter(page, 'caissier');
    await page.goto('/finances/encaissements');
    await page.locator('#onglet-tous').click();
    await expect(page).toHaveURL(/onglet=tous/);

    for (const colonne of ['Encaissement', 'Élève / Payeur', 'Caisse / Caissier', 'Montant', 'Actions']) {
      await expect(page.locator('#table-tous thead th').filter({ hasText: colonne })).toBeVisible();
    }
    const ligne = page.locator('#table-tous tbody tr').filter({ hasText: ELEVE }).first();
    await expect(ligne).toContainText('E2E Caissier');
    await expect(ligne.locator('td').nth(2)).not.toContainText('—');

    // Détail de l'encaissement
    await ligne.locator('.btn-action').click();
    await ligne.locator('.btn-detail').click();
    const detail = page.locator('#detailModal');
    await expect(detail.locator('.resume-paiement')).toContainText(ELEVE);
    await expect(detail.locator('.resume-lignes')).toContainText('Encaissé');
    await expect(detail.locator('.resume-lignes')).toContainText('E2E Caissier');
    await expect(detail.locator('#detail-recu')).toBeVisible();
    await detail.locator('.modal-footer').getByRole('button', { name: 'Fermer' }).click();
    await expect(detail).toBeHidden();

    // Recherche par nom d'élève
    await page.locator('#panneau-tous [name=search]').fill('samlan');
    await page.locator('#panneau-tous .btn-filtrer').click();
    await expect(page.locator('#table-tous tbody tr')).toHaveCount(1);
  });

  test('« Paiements en attente » : seul l\'Administrateur annule un paiement', async ({ page }) => {
    // Le caissier n'a pas cette action
    await seConnecter(page, 'caissier');
    await page.goto('/finances/encaissements?onglet=attente');

    // Du plus récent au plus ancien : le dernier paiement saisi vient en premier
    await expect(page.locator('#table-attente tbody tr').first()).toContainText('SAMLAN Komi');
    const enAttente = page.locator('#table-attente tbody tr').filter({ hasText: 'SAMLAN Komi' });
    await expect(enAttente).toContainText(/10\s000/);
    await enAttente.locator('.btn-action').click();
    await expect(enAttente.locator('.btn-annuler-paiement')).toHaveCount(0);

    await seConnecter(page, 'admin');
    await page.goto('/finances/encaissements?onglet=attente');
    const ligne = page.locator('#table-attente tbody tr').filter({ hasText: 'SAMLAN Komi' });
    await ligne.locator('.btn-action').click();
    await ligne.locator('.btn-annuler-paiement').click();
    const modale = page.locator('#annulerPaiementModal');
    await expect(modale).toBeVisible();
    await modale.locator('[name=motif]').fill('Paiement saisi en double');
    await modale.locator('button[type=submit]').click();

    await attendreToast(page, 'Paiement annulé');
    await expect(page.locator('#table-attente tbody')).not.toContainText('SAMLAN Komi');
  });
});
