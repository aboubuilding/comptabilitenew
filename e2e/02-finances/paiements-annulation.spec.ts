import type { Page } from '@playwright/test';
import { attendreToast, confirmer, encaisserPaiement, expect, ouvrirSaisiePaiement, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité :
 *   2.2 « Modifier ou annuler un paiement non encore encaissé » (Comptable) ;
 *   2.3 « Annuler un encaissement avant clôture de caisse, avec motif » (Caissier).
 */
test.describe.configure({ mode: 'serial' });

const MATRICULE = 'E2E0002';
const PRODUIT = 'T-shirt M';
const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

async function stockDe(page: Page): Promise<number> {
  await seConnecter(page, 'achats');
  await page.goto('/logistique/produits');
  const texte = await ligneDe(page, PRODUIT).locator('td.num.grand').innerText();
  return Number(texte.replace(/\s/g, '').replace(',', '.'));
}

/** Le Comptable enregistre la vente d'un T-shirt par le panier ; renvoie la référence du paiement. */
async function saisirPaiementProduit(page: Page): Promise<string> {
  await seConnecter(page, 'comptable');
  await ouvrirSaisiePaiement(page, MATRICULE);

  await page.locator('#search-panier').fill('T-shirt');
  await page.locator('#search-results .search-result-item', { hasText: PRODUIT }).click();
  await expect(page.locator('#total-montant')).toHaveText(/6\s500,00/);
  await page.locator('#btn-submit').click();
  await attendreToast(page);

  await page.goto('/finances/paiements');
  return (await page.locator('tbody tr').first().locator('.reference').innerText()).trim();
}

async function ouvrirCaisseSiBesoin(page: Page): Promise<void> {
  await seConnecter(page, 'caissier');
  await page.goto('/finances/caisses');
  if ((await page.locator('tbody tr').filter({ hasText: 'Ouverte' }).count()) > 0) return;

  await page.locator('[data-bs-target="#caisseModal"]').click();
  const modale = page.locator('#caisseModal');
  await modale.locator('[name=libelle]').fill('Caisse annulations');
  await modale.locator('[name=solde_initial]').fill('0');
  await modale.locator('button[type=submit]').click();
  await attendreToast(page);
}

test.describe('2.2 / 2.3 Annulations', () => {
  let reference = '';

  test('le Comptable annule un paiement non encore encaissé : il quitte la file du Caissier', async ({ page }) => {
    const annule = await saisirPaiementProduit(page);

    const paiement = ligneDe(page, annule);
    await expect(paiement).toContainText('En attente d\'encaissement');
    await paiement.locator('.btn-action').click();
    await paiement.getByRole('button', { name: 'Annuler' }).click();
    await confirmer(page);
    await attendreToast(page);
    await expect(ligneDe(page, annule)).toContainText('Annulé');

    // Un paiement annulé n'est plus proposé à l'encaissement
    await seConnecter(page, 'caissier');
    await page.goto('/finances/encaissements?onglet=attente');
    await expect(page.locator('#table-attente tbody')).not.toContainText(annule);
  });

  test('un paiement encaissé ne peut plus être annulé par le Comptable', async ({ page }) => {
    const stockAvant = await stockDe(page);
    reference = await saisirPaiementProduit(page);

    await ouvrirCaisseSiBesoin(page);
    await encaisserPaiement(page, reference);
    await expect(page.locator('#table-caisse tbody')).toContainText(reference);

    // Le produit est sorti du stock
    expect(await stockDe(page)).toBe(stockAvant - 1);

    await seConnecter(page, 'comptable');
    await page.goto('/finances/paiements');
    const paiement = ligneDe(page, reference);
    await expect(paiement).toContainText('Encaissé');
    await paiement.locator('.btn-action').click();
    await expect(paiement.getByRole('button', { name: 'Annuler' })).toHaveCount(0);
    await expect(paiement).toContainText('Verrouillé');
  });

  test('le Caissier annule l\'encaissement avec un motif : retour en file d\'attente, stock rétabli', async ({ page }) => {
    const stockAvant = await stockDe(page);

    await seConnecter(page, 'caissier');
    await page.goto('/finances/encaissements?onglet=caisse');
    const encaisse = page.locator('#table-caisse tbody tr').filter({ hasText: reference });
    await encaisse.locator('.btn-action').click();
    await encaisse.locator('.btn-annuler-encaissement').click();
    await expect(page.locator('#annuler-reference')).toHaveText(reference);

    // Le motif est obligatoire
    await page.locator('#annuler-form button[type=submit]').click();
    await expect(page.locator('#annulerModal')).toBeVisible();

    await page.locator('#annuler-form [name=motif]').fill('Erreur de saisie du montant');
    await page.locator('#annuler-form button[type=submit]').click();
    await attendreToast(page);

    // Le paiement est revenu dans les paiements en attente, et sort de mes encaissements du jour
    await expect(page).toHaveURL(/onglet=attente/);
    await expect(page.locator('#table-attente tbody')).toContainText(reference);
    await expect(page.locator('#table-caisse tbody')).not.toContainText(reference);

    // Le produit est revenu en stock, et le paiement est de nouveau en attente
    expect(await stockDe(page)).toBe(stockAvant + 1);
    await seConnecter(page, 'comptable');
    await page.goto('/finances/paiements');
    await expect(ligneDe(page, reference)).toContainText('En attente d\'encaissement');
  });

  test('remise en état : le paiement est finalement encaissé', async ({ page }) => {
    await seConnecter(page, 'caissier');
    await encaisserPaiement(page, reference);
    await page.goto('/finances/encaissements?onglet=attente');
    await expect(page.locator('#table-attente tbody')).not.toContainText(reference);
  });
});
