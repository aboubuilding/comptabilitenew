import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 3 — Logistique & Achats :
 *   3.1 Fournisseurs, 3.2 Achats, 3.3 Produits & Stock.
 * Profils : Responsable Achats & Stock (gestion), Comptable (suivi du paiement
 * des achats), Responsables Cantine et Boutique (consultation du stock).
 */
test.describe.configure({ mode: 'serial' });

const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

/** Stock total d'un produit, lu sur l'écran Produits & Stock. */
async function stockDe(page: Page, produit: string): Promise<number> {
  await page.goto('/logistique/produits');
  const texte = await ligneDe(page, produit).locator('td.num.grand').innerText();
  return Number(texte.replace(/\s/g, '').replace(',', '.'));
}

test.describe('3.1 Fournisseurs', () => {
  test('créer puis modifier une fiche fournisseur', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/fournisseurs');

    for (const colonne of ['Raison sociale', 'Contact', 'Téléphone', 'Achats', 'Montant total']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    await expect(ligneDe(page, 'Papeterie du Centre')).toContainText('90111111');

    await page.locator('#btnNouveauFournisseur').click();
    await page.locator('#f-raison').fill('Alimentation Générale du Golfe');
    await page.locator('#f-contact').fill('Mme ADJOVI');
    await page.locator('#f-telephone').fill('90555555');
    await page.locator('#f-email').fill('contact@agg.exemple.test');
    await page.locator('#f-adresse').fill('Marché de Hédzranawoé');
    await page.locator('#fournisseur-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = ligneDe(page, 'Alimentation Générale du Golfe');
    await expect(ligne).toContainText('Mme ADJOVI');
    await expect(ligne).toContainText('90555555');

    await actionDeLigne(page, 'Alimentation Générale du Golfe', 'Modifier');
    await expect(page.locator('#f-raison')).toHaveValue('Alimentation Générale du Golfe');
    await expect(page.locator('#f-email')).toHaveValue('contact@agg.exemple.test');
    await page.locator('#f-telephone').fill('90555556');
    await page.locator('#fournisseur-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Alimentation Générale du Golfe')).toContainText('90555556');
  });

  test('un fournisseur sans raison sociale est refusé ; la liste s\'exporte', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/fournisseurs');

    await page.locator('#btnNouveauFournisseur').click();
    await page.locator('#fournisseur-form button[type=submit]').click();
    await expect(page.locator('#fournisseurModal')).toBeVisible();
    await expect(page.locator('#fournisseurModal [id$="-error"]').filter({ hasText: /\S/ }).first()).toBeVisible();

    for (const motif of ['/fournisseurs/export', 'export-pdf']) {
      const lien = await page.locator(`a[href*="${motif}"]`).first().getAttribute('href');
      expect((await page.request.get(lien!)).status(), motif).toBe(200);
    }
  });
});

test.describe('3.2 Achats', () => {
  test('créer un achat avec ses lignes : en attente de livraison, non payé, stock inchangé', async ({ page }) => {
    await seConnecter(page, 'achats');
    const stockAvant = await stockDe(page, 'Riz (kg)');
    expect(stockAvant).toBe(100);

    await page.goto('/logistique/achats');
    await page.locator('#btnNouvelAchat').click();
    const modale = page.locator('#achatModal');
    await modale.locator('[name=fournisseur_id]').selectOption({ label: 'Alimentation Générale du Golfe' });
    await modale.locator('[name=type_achat]').selectOption({ label: 'Achat de stock' });
    await modale.locator('[name=nom_acheteur]').fill('E2E Achats');
    await modale.locator('[name=bon_commande]').fill('BC-2026-001');

    if ((await modale.locator('.ligne-form').count()) === 0) await modale.locator('#add-ligne').click();
    const ligne = modale.locator('.ligne-form').first();
    const riz = await ligne.locator('select option', { hasText: 'Riz (kg)' }).getAttribute('value');
    await ligne.locator('select').selectOption(riz!);
    await ligne.locator('input[name$="[quantite]"]').fill('50');
    await ligne.locator('input[name$="[prix_unitaire]"]').fill('550');
    await expect(ligne.locator('.ligne-total')).toHaveText(/27\s500,00/);
    await expect(modale.locator('#total-achat')).toHaveText(/27\s500,00/);

    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    for (const colonne of ['Référence', 'Fournisseur', 'Date', 'Montant', 'Livraison', 'Paiement']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    const achat = ligneDe(page, 'Alimentation Générale du Golfe');
    await expect(achat).toContainText(/27\s500,00/);
    await expect(achat).toContainText('En attente');
    await expect(achat).toContainText('Non payé');

    // Tant que l'achat n'est pas réceptionné, le stock ne bouge pas
    expect(await stockDe(page, 'Riz (kg)')).toBe(stockAvant);
  });

  test('un achat sans ligne est refusé', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/achats');
    await page.locator('#btnNouvelAchat').click();
    const modale = page.locator('#achatModal');
    await modale.locator('[name=fournisseur_id]').selectOption({ label: 'Papeterie du Centre' });
    for (const retrait of await modale.locator('.remove-ligne').all()) await retrait.click().catch(() => undefined);
    await modale.locator('#achat-form').evaluate((f: HTMLFormElement) => (f.noValidate = true));
    await modale.locator('.ligne-form select').first().evaluate((s: HTMLSelectElement) => s.closest('.ligne-form')?.remove()).catch(() => undefined);
    await modale.locator('button[type=submit]').click();

    await expect(modale).toBeVisible();
    await expect(page.locator('#toast-container .toast-error').last()).toBeVisible();
  });

  test('réceptionner l\'achat met le stock à jour et trace le mouvement', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/achats');

    const achat = ligneDe(page, 'Alimentation Générale du Golfe');
    await achat.locator('.btn-action').click();
    await achat.getByRole('button', { name: 'Réceptionner' }).click();
    await confirmer(page);
    await attendreToast(page);
    await expect(ligneDe(page, 'Alimentation Générale du Golfe')).toContainText('Livré');

    // 100 + 50 = 150
    expect(await stockDe(page, 'Riz (kg)')).toBe(150);

    // Historique des mouvements de stock
    await page.goto('/logistique/mouvements-stock');
    const mouvement = ligneDe(page, 'Riz (kg)').first();
    await expect(mouvement).toContainText(/50/);
    await expect(mouvement).toContainText(/550/);

    // Un achat livré ne se réceptionne pas deux fois
    await page.goto('/logistique/achats');
    const livre = ligneDe(page, 'Alimentation Générale du Golfe');
    await livre.locator('.btn-action').click();
    await expect(livre.getByRole('button', { name: 'Réceptionner' })).toHaveCount(0);
  });

  test('la fiche d\'un achat détaille ses lignes ; le fournisseur cumule ses achats', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/achats');
    await ligneDe(page, 'Alimentation Générale du Golfe').locator('a').first().click();

    await expect(page.locator('.page-title-main')).toContainText('Détail de l\'achat');
    const detail = ligneDe(page, 'Riz (kg)');
    await expect(detail).toContainText('50');
    await expect(detail).toContainText(/27\s500,00/);

    await page.goto('/logistique/fournisseurs');
    const fournisseur = ligneDe(page, 'Alimentation Générale du Golfe');
    await expect(fournisseur).toContainText(/27\s500/);
    await fournisseur.locator('a').first().click();
    await expect(page.locator('main')).toContainText('Alimentation Générale du Golfe');
    await expect(page.locator('main')).toContainText(/27\s500/);
  });

  test('le Comptable suit les achats sans pouvoir en créer ni les réceptionner', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/logistique/achats');

    await expect(ligneDe(page, 'Alimentation Générale du Golfe')).toContainText('Livré');
    await expect(page.locator('#btnNouvelAchat')).toHaveCount(0);

    const export_ = await page.request.get(await page.locator('a[href*="/achats/export"]').getAttribute('href').then((h) => h!));
    expect(export_.status()).toBe(200);
  });
});

test.describe('3.3 Produits & Stock', () => {
  test('créer un produit avec ses unités, puis le modifier', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/produits');

    for (const colonne of ['Produit', 'Type', 'Prix unitaire', 'Stock total', 'Valeur', 'État']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    await page.locator('#btnNouveauProduit').click();
    await page.locator('#p-libelle').fill('Cahier 200 pages');
    await page.locator('#p-type').selectOption({ label: 'Vendable (boutique)' });
    await page.locator('#p-unite-stock').fill('unité');
    await page.locator('#p-unite-achat').fill('carton');
    await page.locator('#p-equivalence').fill('50');
    await page.locator('#p-prix').fill('800');
    await page.locator('#produit-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = ligneDe(page, 'Cahier 200 pages');
    await expect(ligne).toContainText('Vendable');
    await expect(ligne).toContainText('800,00');
    await expect(ligne).toContainText('Rupture');

    await actionDeLigne(page, 'Cahier 200 pages', 'Modifier');
    await expect(page.locator('#p-libelle')).toHaveValue('Cahier 200 pages');
    await expect(page.locator('#p-unite-achat')).toHaveValue('carton');
    await page.locator('#p-prix').fill('850');
    await page.locator('#produit-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Cahier 200 pages')).toContainText('850,00');
  });

  test('un bon d\'entrée alimente le stock du magasin choisi', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/bons');

    await page.locator('#btnNouveauBon').click();
    const modale = page.locator('#bonModal');
    await modale.locator('#bon-type').selectOption({ label: 'Bon d\'entrée' });
    await modale.locator('[name=magasin_id]').selectOption({ label: 'Boutique scolaire' });
    await modale.locator('[name=motif]').fill('Dotation de rentrée');
    if ((await modale.locator('.ligne-form').count()) === 0) await modale.locator('#add-ligne-bon').click();
    const ligne = modale.locator('.ligne-form').first();
    await ligne.locator('select').selectOption({ label: 'Cahier 200 pages' });
    await ligne.locator('input[name$="[quantite]"]').fill('40');
    await ligne.locator('input[name$="[prix_unitaire]"]').fill('500');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    const bon = ligneDe(page, 'Dotation de rentrée');
    await expect(bon).toContainText('entrée');
    await expect(bon).toContainText('Boutique scolaire');

    expect(await stockDe(page, 'Cahier 200 pages')).toBe(40);
    await expect(ligneDe(page, 'Cahier 200 pages')).toContainText('Disponible');
  });

  test('un bon de transfert déplace le stock entre deux magasins sans changer le total', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/bons');

    await page.locator('#btnNouveauBon').click();
    const modale = page.locator('#bonModal');
    await modale.locator('#bon-type').selectOption({ label: 'Bon de transfert' });
    await expect(modale.locator('#destination-wrap')).toBeVisible();
    await modale.locator('[name=magasin_id]').selectOption({ label: 'Entrepôt central' });
    await modale.locator('[name=magasin_destination_id]').selectOption({ label: 'Boutique scolaire' });
    await modale.locator('[name=motif]').fill('Réassort boutique');
    if ((await modale.locator('.ligne-form').count()) === 0) await modale.locator('#add-ligne-bon').click();
    const ligne = modale.locator('.ligne-form').first();
    await ligne.locator('select').selectOption({ label: 'Riz (kg)' });
    await ligne.locator('input[name$="[quantite]"]').fill('30');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    // Total inchangé (150), mais réparti : 120 à l'entrepôt, 30 à la boutique
    expect(await stockDe(page, 'Riz (kg)')).toBe(150);
    await ligneDe(page, 'Riz (kg)').locator('a').first().click();
    const parMagasin = page.locator('main');
    await expect(parMagasin.locator('tr', { hasText: 'Entrepôt central' }).first()).toContainText('120');
    await expect(parMagasin.locator('tr', { hasText: 'Boutique scolaire' }).first()).toContainText('30');
  });

  test('un bon de sortie ne peut pas dépasser le stock disponible', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/bons');

    await page.locator('#btnNouveauBon').click();
    const modale = page.locator('#bonModal');
    await modale.locator('#bon-type').selectOption({ label: 'Bon de sortie' });
    await modale.locator('[name=magasin_id]').selectOption({ label: 'Boutique scolaire' });
    await modale.locator('[name=motif]').fill('Sortie excessive');
    if ((await modale.locator('.ligne-form').count()) === 0) await modale.locator('#add-ligne-bon').click();
    const ligne = modale.locator('.ligne-form').first();
    await ligne.locator('select').selectOption({ label: 'Cahier 200 pages' });
    await ligne.locator('input[name$="[quantite]"]').fill('500');
    await modale.locator('button[type=submit]').click();

    await expect(page.locator('#toast-container .toast-error').last()).toContainText(/Stock insuffisant/i);
    expect(await stockDe(page, 'Cahier 200 pages')).toBe(40);
  });

  test('un ajustement d\'inventaire corrige le stock et exige un motif', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/mouvements-stock');

    await page.locator('[data-bs-target="#ajusterModal"]').click();
    const modale = page.locator('#ajusterModal');
    await modale.locator('[name=produit_id]').selectOption({ label: 'Cahier 200 pages' });
    await modale.locator('[name=magasin_id]').selectOption({ label: 'Boutique scolaire' });
    await modale.locator('[name=nouvelle_quantite]').fill('38');
    await modale.locator('[name=motif]').fill('Deux cahiers abîmés constatés à l\'inventaire');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    expect(await stockDe(page, 'Cahier 200 pages')).toBe(38);

    const export_ = await page.request.get('/logistique/mouvements-stock/export');
    expect(export_.status()).toBe(200);
  });

  test('le tableau de bord du stock et la fiche d\'un magasin reflètent les quantités', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/tableau');
    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Logistique' }).first();
    await menu.hover();
    await menu.locator('.hnav-drop-item', { hasText: 'Tableau de bord du stock' }).click();

    await expect(page.locator('main')).toContainText('Entrepôt central');
    await expect(page.locator('main')).toContainText('Boutique scolaire');

    await page.goto('/logistique/magasins');
    await ligneDe(page, 'Boutique scolaire').locator('a').first().click();
    await expect(page.locator('main')).toContainText('Cahier 200 pages');
    await expect(page.locator('main')).toContainText('38');
  });

  test('les Responsables Cantine et Boutique consultent le stock sans le modifier', async ({ page }) => {
    for (const profil of ['cantine', 'boutique'] as const) {
      await seConnecter(page, profil);
      await page.goto('/logistique/produits');
      await expect(ligneDe(page, 'Riz (kg)')).toBeVisible();
      await expect(page.locator('#btnNouveauProduit')).toHaveCount(0);
      await expect(page.locator('.btn-edit-produit')).toHaveCount(0);

      await page.goto('/logistique/bons');
      await expect(page.locator('#btnNouveauBon')).toHaveCount(0);

      const refus = await page.request.get('/logistique/achats', { maxRedirects: 0 });
      expect(refus.status(), `${profil} → achats`).toBe(403);
    }
  });
});
