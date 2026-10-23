import type { Page } from '@playwright/test';
import { attendreToast, choisirEleve, expect, ouvrirSaisiePaiement, seConnecter, test } from '../support/test';

/**
 * Menu 4 — Services aux Élèves › 4.2 Cantine scolaire
 * Profil concerné : Responsable Cantine (qui consulte aussi le stock, § 3.3).
 */
test.describe.configure({ mode: 'serial' });

const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

async function stockDe(page: Page, produit: string): Promise<number> {
  await page.goto('/logistique/produits');
  const texte = await ligneDe(page, produit).locator('td.num.grand').innerText();
  return Number(texte.replace(/\s/g, '').replace(',', '.'));
}

test.describe('4.2 Cantine scolaire', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'cantine');
  });

  test('souscrire un élève à la cantine : le dû apparaît aussitôt, sans paiement', async ({ page }) => {
    await page.goto('/services-eleves/cantine/inscriptions');

    for (const colonne of ['Élève', 'Classe', 'Période', 'Mensuel', 'Total dû', 'Statut']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    await page.locator('#btnNouvelleInscription').click();
    const modale = page.locator('#inscriptionModal');
    await choisirEleve(page, '#inscriptionModal', 'Kossi', 'E2E0002');
    await modale.locator('[name=date_debut]').fill('2026-10-01');
    await modale.locator('[name=montant_mensuel]').fill('12000');
    await modale.locator('[name=nombre_mois]').fill('9');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    // 12 000 × 9 mois = 108 000
    const inscription = ligneDe(page, 'KOFFI Kossi');
    await expect(inscription).toContainText('CP A');
    await expect(inscription).toContainText(/12\s000/);
    await expect(inscription).toContainText(/108\s000/);
    await expect(inscription).toContainText('Actif');

    // Le Comptable voit l'engagement « cantine » sur l'écran Paiements
    await seConnecter(page, 'comptable');
    await ouvrirSaisiePaiement(page, 'E2E0002');
    await expect(page.locator('.engagement-row.exists').filter({ hasText: /cantine/i })).toContainText(/108\s000,00/);
  });

  test('un élève ne peut pas être inscrit deux fois à la cantine', async ({ page }) => {
    await page.goto('/services-eleves/cantine/inscriptions');
    await page.locator('#btnNouvelleInscription').click();
    const modale = page.locator('#inscriptionModal');
    await choisirEleve(page, '#inscriptionModal', 'Kossi', 'E2E0002');
    await modale.locator('[name=montant_mensuel]').fill('12000');
    await modale.locator('button[type=submit]').click();

    await expect(page.locator('#toast-container .toast-error').last()).toBeVisible();
    await page.reload();
    await expect(ligneDe(page, 'KOFFI Kossi')).toHaveCount(1);
  });

  test('créer un menu composé à partir des produits en stock, avec son coût prévisionnel', async ({ page }) => {
    await page.goto('/services-eleves/cantine/menus');

    await page.locator('#btnNouveauMenu').click();
    await page.locator('#menu-libelle').fill('Riz sauce arachide');
    await page.locator('#menu-date').fill('2026-10-05');
    await page.locator('#menu-type').selectOption({ label: 'Déjeuner' });
    await page.locator('#menu-magasin').selectOption({ label: 'Entrepôt central' });
    await page.locator('#menu-quantite').fill('100');

    if ((await page.locator('#menu-produits .ligne-produit').count()) === 0) await page.locator('#add-produit-menu').click();
    const ligne = page.locator('#menu-produits .ligne-produit').first();
    await ligne.locator('.produit-select').selectOption({ label: 'Riz (kg)' });
    await ligne.locator('input[name$="[quantite]"]').fill('20');
    await ligne.locator('input[name$="[cout_unitaire]"]').fill('600');

    // 20 kg × 600 = 12 000
    await expect(page.locator('#menu-cout-prevu')).toHaveText(/12\s000,00/);
    await page.locator('#menu-form button[type=submit]').click();
    await attendreToast(page);

    for (const colonne of ['Libellé', 'Date de service', 'Type', 'Prévu', 'Coût prévu', 'Coût réel']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    const menu = ligneDe(page, 'Riz sauce arachide');
    await expect(menu).toContainText('05/10/2026');
    await expect(menu).toContainText('Déjeuner');
    await expect(menu).toContainText('100');
    await expect(menu).toContainText(/12\s000/);

    // Composition consultable
    await menu.locator('.btn-action').click();
    await menu.locator('.btn-detail-menu').click();
    await expect(page.locator('#menu-detail-body')).toContainText('Riz (kg)');
    await expect(page.locator('#menu-detail-body')).toContainText('20');
  });

  test('enregistrer une préparation de repas : coût réel et sortie de stock proportionnelle', async ({ page }) => {
    const stockAvant = await stockDe(page, 'Riz (kg)');

    await page.goto('/services-eleves/cantine/preparations');
    await page.locator('#btnNouvellePrep').click();
    const formulaire = page.locator('#preparation-form');
    const menu = await formulaire.locator('[name=menu_id] option', { hasText: 'Riz sauce arachide' }).getAttribute('value');
    await formulaire.locator('[name=menu_id]').selectOption(menu!);
    await formulaire.locator('[name=magasin_id]').selectOption({ label: 'Entrepôt central' });
    await formulaire.locator('[name=date_preparation]').fill('2026-10-05');
    await formulaire.locator('[name=nombre_parts]').fill('50');
    await formulaire.locator('[name=cout_reel]').fill('6500');
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    for (const colonne of ['Date', 'Menu', 'Magasin', 'Parts', 'Coût réel', 'Coût / part', 'Stock']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    // 6 500 pour 50 parts → 130 par part
    const preparation = ligneDe(page, 'Riz sauce arachide');
    await expect(preparation).toContainText('Entrepôt central');
    await expect(preparation).toContainText('50');
    await expect(preparation).toContainText(/6\s500/);
    await expect(preparation).toContainText('130');
    await expect(preparation).toContainText('Déduit');

    // 50 parts sur 100 prévues → la moitié des 20 kg : 10 kg sortis du stock
    expect(await stockDe(page, 'Riz (kg)')).toBe(stockAvant - 10);

    // Le menu affiche son coût réel
    await page.goto('/services-eleves/cantine/menus');
    await expect(ligneDe(page, 'Riz sauce arachide')).toContainText(/6\s500/);
  });

  test('une préparation qui dépasse le stock est refusée', async ({ page }) => {
    const stockAvant = await stockDe(page, 'Riz (kg)');

    await page.goto('/services-eleves/cantine/preparations');
    await page.locator('#btnNouvellePrep').click();
    const formulaire = page.locator('#preparation-form');
    const menu = await formulaire.locator('[name=menu_id] option', { hasText: 'Riz sauce arachide' }).getAttribute('value');
    await formulaire.locator('[name=menu_id]').selectOption(menu!);
    await formulaire.locator('[name=magasin_id]').selectOption({ label: 'Entrepôt central' });
    await formulaire.locator('[name=nombre_parts]').fill('100000');
    await formulaire.locator('button[type=submit]').click();

    await expect(page.locator('#toast-container .toast-error').last()).toContainText(/Stock insuffisant/i);
    expect(await stockDe(page, 'Riz (kg)')).toBe(stockAvant);
  });

  test('gérer l\'abandon d\'un abonnement cantine', async ({ page }) => {
    await page.goto('/services-eleves/cantine/inscriptions');

    const inscription = ligneDe(page, 'KOFFI Kossi');
    await inscription.locator('.btn-action').click();
    await inscription.locator('.btn-abandonner').click();
    await page.locator('#abandon-form [name=motif]').fill('Régime alimentaire particulier');
    await page.locator('#abandon-form button[type=submit]').click();
    await attendreToast(page);

    await expect(ligneDe(page, 'KOFFI Kossi')).toContainText('Abandonné');
  });

  test('le tableau de bord cantine et les exports', async ({ page }) => {
    await page.goto('/tableau');
    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Services aux Élèves' }).first();
    await menu.hover();
    const lien = menu.locator('.hnav-drop-item', { hasText: 'Tableau de bord cantine' });
    // Menu long : le groupe ouvre ses écrans dans un second niveau
    const groupe = menu.locator('.hnav-sub').filter({ has: page.locator('.hnav-drop-item', { hasText: 'Tableau de bord cantine' }) });
    if (await groupe.count()) await groupe.hover();
    await lien.click();
    await expect(page.locator('main.content-area')).toBeVisible();

    const export_ = await page.request.get('/services-eleves/cantine/inscriptions/export');
    expect(export_.status()).toBe(200);
  });

  test('le Responsable Cantine consulte le stock mais ne gère ni les achats ni le transport', async ({ page }) => {
    await page.goto('/logistique/produits');
    await expect(ligneDe(page, 'Riz (kg)')).toBeVisible();

    for (const chemin of ['/logistique/achats', '/services-eleves/transport/voitures', '/services-eleves/bibliotheque/livres']) {
      const reponse = await page.request.get(chemin, { maxRedirects: 0 });
      expect(reponse.status(), chemin).toBe(403);
    }
  });
});
