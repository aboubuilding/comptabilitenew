import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, expect, seConnecter, test } from '../support/test';

/**
 * Menu 3 — Logistique & Achats › 3.4 Boutique / Point de vente
 * Profil concerné : Responsable Boutique / Point de vente.
 *
 * Ce fichier travaille sur un produit qui lui est propre (créé et approvisionné
 * ici), pour ne pas dépendre du stock manipulé par les autres scénarios.
 */
test.describe.configure({ mode: 'serial' });

const PRODUIT = 'Gourde isotherme';
const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

async function stockDe(page: Page, produit: string): Promise<number> {
  await page.goto('/logistique/produits');
  const texte = await ligneDe(page, produit).locator('td.num.grand').innerText();
  return Number(texte.replace(/\s/g, '').replace(',', '.'));
}

test.describe('3.4 Boutique / Point de vente', () => {
  test('préparation : un produit vendable est créé et mis en rayon (25 unités à la boutique)', async ({ page }) => {
    await seConnecter(page, 'achats');
    await page.goto('/logistique/produits');
    await page.locator('#btnNouveauProduit').click();
    await page.locator('#p-libelle').fill(PRODUIT);
    await page.locator('#p-type').selectOption({ label: 'Vendable (boutique)' });
    await page.locator('#p-unite-stock').fill('unité');
    await page.locator('#p-prix').fill('4000');
    await page.locator('#produit-form button[type=submit]').click();
    await attendreToast(page);

    await page.goto('/logistique/bons');
    await page.locator('#btnNouveauBon').click();
    const modale = page.locator('#bonModal');
    await modale.locator('#bon-type').selectOption({ label: 'Bon d\'entrée' });
    await modale.locator('[name=magasin_id]').selectOption({ label: 'Boutique scolaire' });
    await modale.locator('[name=motif]').fill('Mise en rayon gourdes');
    if ((await modale.locator('.ligne-form').count()) === 0) await modale.locator('#add-ligne-bon').click();
    const ligne = modale.locator('.ligne-form').first();
    await ligne.locator('select').selectOption({ label: PRODUIT });
    await ligne.locator('input[name$="[quantite]"]').fill('25');
    await ligne.locator('input[name$="[prix_unitaire]"]').fill('2500');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    expect(await stockDe(page, PRODUIT)).toBe(25);
  });

  test('créer puis modifier un client externe', async ({ page }) => {
    await seConnecter(page, 'boutique');
    await page.goto('/logistique/clients');

    await page.locator('#btnNouveauClient').click();
    await page.locator('#c-nom').fill('Association des Parents d\'Élèves');
    await page.locator('#c-type').selectOption({ index: 1 });
    await page.locator('#c-tel').fill('90666666');
    await page.locator('#c-email').fill('ape@exemple.test');
    await page.locator('#client-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Association des Parents')).toContainText('90666666');

    await actionDeLigne(page, 'Association des Parents', 'Modifier');
    await expect(page.locator('#c-nom')).toHaveValue('Association des Parents d\'Élèves');
    await page.locator('#c-tel').fill('90666667');
    await page.locator('#client-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Association des Parents')).toContainText('90666667');
  });

  test('le point de vente affiche le stock disponible au moment de la vente', async ({ page }) => {
    await seConnecter(page, 'boutique');
    await page.goto('/logistique/pos');
    await expect(page.locator('#select-magasin option:checked')).toHaveText(/Boutique scolaire/);

    await page.locator('#recherche-produit').fill('Gourde');
    const resultat = page.locator('#search-results .result-item').filter({ hasText: PRODUIT });
    await expect(resultat).toContainText('Stock : 25');
    await expect(resultat).toContainText(/4\s000,00/);
    await expect(page.locator('#btn-encaisser')).toBeDisabled();
  });

  test('enregistrer une vente avec remise pour un élève, et éditer le ticket', async ({ page, context }) => {
    await seConnecter(page, 'boutique');
    await page.goto('/logistique/pos');

    await page.locator('#recherche-produit').fill('Gourde');
    await page.locator('#search-results .result-item').filter({ hasText: PRODUIT }).click();

    // 3 gourdes à 4 000, remise de 10 % → 10 800
    const ligne = page.locator('#panier-body tr').first();
    await ligne.locator('input[data-field=quantite]').fill('3');
    await ligne.locator('input[data-field=remise]').fill('10');
    await expect(page.locator('#total-ttc')).toHaveText(/10\s800,00/);
    await expect(page.locator('#total-remise')).toHaveText(/1\s200,00/);

    // Vente associée à l'inscription d'un élève, choisi par son nom
    const eleve = await page.locator('#inscription-id option', { hasText: 'KOFFI Ama' }).getAttribute('value');
    await page.locator('#inscription-id').selectOption(eleve!);

    const ticket = context.waitForEvent('page');
    await page.locator('#btn-encaisser').click();
    const onglet = await ticket;
    await attendreToast(page, 'Vente');
    await onglet.waitForLoadState().catch(() => undefined);
    await onglet.close().catch(() => undefined);

    // L'écran se recharge de lui-même pour rafraîchir les ventes du jour
    await page.waitForEvent('load');
    await expect(page.locator('main')).toContainText(/10\s800/);

    // Historique : référence, date, montant, statut de paiement
    await page.goto('/logistique/ventes');
    for (const colonne of ['Référence', 'Date', 'Acheteur', 'Montant', 'Statut']) {
      await expect(page.locator('thead th').filter({ hasText: new RegExp(colonne, 'i') }).first()).toBeVisible();
    }
    const vente = page.locator('tbody tr').first();
    await expect(vente).toContainText(/10\s800,00/);
    await expect(vente).toContainText('Payé');
    await expect(vente).toContainText('KOFFI');

    // Ticket de vente
    const lienTicket = await vente.locator('a[href*="/ticket"]').getAttribute('href');
    const reponse = await page.request.get(lienTicket!);
    expect(reponse.status()).toBe(200);
    expect(reponse.headers()['content-type']).toContain('pdf');

    // Le stock a été décrémenté : 25 − 3 = 22
    await seConnecter(page, 'achats');
    expect(await stockDe(page, PRODUIT)).toBe(22);
  });

  test('une vente ne peut pas dépasser le stock de la boutique', async ({ page }) => {
    await seConnecter(page, 'boutique');
    await page.goto('/logistique/pos');

    await page.locator('#recherche-produit').fill('Gourde');
    await page.locator('#search-results .result-item').filter({ hasText: PRODUIT }).click();
    await page.locator('#panier-body tr').first().locator('input[data-field=quantite]').fill('999');

    // La quantité est ramenée au stock disponible, avec un message explicite
    await expect(page.locator('#toast-container .toast-error').last()).toContainText('Quantité limitée au stock disponible (22)');
    await expect(page.locator('#panier-body tr').first().locator('input[data-field=quantite]')).toHaveValue('22');
    await expect(page.locator('#total-ttc')).toHaveText(/88\s000,00/);

    await seConnecter(page, 'achats');
    expect(await stockDe(page, PRODUIT)).toBe(22);
  });

  test('une vente à crédit pour un client externe est marquée payée plus tard', async ({ page }) => {
    await seConnecter(page, 'boutique');
    await page.goto('/logistique/ventes');

    await page.locator('#btnNouvelleVente').click();
    const modale = page.locator('#venteModal');
    await modale.locator('[name=magasin_id]').selectOption({ label: 'Boutique scolaire' });
    const client = await modale.locator('[name=client_id] option', { hasText: 'Association des Parents' }).getAttribute('value');
    await modale.locator('[name=client_id]').selectOption(client!);
    if ((await modale.locator('.ligne-form').count()) === 0) await modale.locator('#add-ligne-vente').click();
    const ligne = modale.locator('.ligne-form').first();
    const produit = await ligne.locator('select option', { hasText: PRODUIT }).getAttribute('value');
    await ligne.locator('select').selectOption(produit!);
    await ligne.locator('input[name$="[quantite]"]').fill('2');
    await expect(ligne.locator('input[name$="[prix_unitaire]"]')).toHaveValue(/^4000/);
    await modale.locator('#marquer-payee').uncheck();
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    let vente = ligneDe(page, 'Association des Parents');
    await expect(vente).toContainText(/8\s000,00/);
    await expect(vente).toContainText('Impayé');

    await vente.locator('.btn-action').click();
    await vente.getByRole('button', { name: 'Marquer payée' }).click();
    await page.locator('.swal2-confirm').click();
    await attendreToast(page);
    vente = ligneDe(page, 'Association des Parents');
    await expect(vente).toContainText('Payé');

    // Filtre par statut de paiement
    const impaye = await page.locator('select[name=statut_paiement] option', { hasText: 'Impayé' }).getAttribute('value');
    await page.locator('select[name=statut_paiement]').selectOption(impaye!);
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).not.toContainText('Association des Parents');
  });

  test('annuler une vente exige un motif et remet les articles en stock', async ({ page }) => {
    await seConnecter(page, 'boutique');
    await page.goto('/logistique/ventes');

    const vente = ligneDe(page, 'Association des Parents');
    await vente.locator('.btn-action').click();
    await vente.locator('.btn-annuler-vente').click();
    await page.locator('#annuler-vente-form [name=motif]').fill('Commande annulée par le client');
    await page.locator('#annuler-vente-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Association des Parents')).toContainText('Annulé');

    // 22 − 2 (vente) + 2 (annulation) = 22
    await seConnecter(page, 'achats');
    expect(await stockDe(page, PRODUIT)).toBe(22);
  });

  test('la fiche d\'une vente détaille ses lignes ; l\'historique et le tableau de bord s\'affichent', async ({ page }) => {
    await seConnecter(page, 'boutique');
    await page.goto('/logistique/ventes');

    await page.locator('tbody tr').filter({ hasText: 'KOFFI' }).locator('a').first().click();
    await expect(page.locator('.page-title-main')).toContainText('Détail de la vente');
    await expect(page.locator('main')).toContainText(PRODUIT);
    await expect(page.locator('main')).toContainText(/10\s800,00/);

    const export_ = await page.request.get('/logistique/ventes/export');
    expect(export_.status()).toBe(200);

    await page.goto('/tableau');
    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Logistique' }).first();
    await menu.hover();
    const lien = menu.locator('.hnav-drop-item', { hasText: 'Tableau de bord boutique' });
    // Menu long : le groupe ouvre ses écrans dans un second niveau
    const groupe = menu.locator('.hnav-sub').filter({ has: page.locator('.hnav-drop-item', { hasText: 'Tableau de bord boutique' }) });
    if (await groupe.count()) await groupe.hover();
    await lien.click();
    await expect(page.locator('main.content-area')).toBeVisible();
    await expect(page.locator('main')).toContainText(/10\s800/);
  });

  test('reversement du carnet papier : les ventes entrent dans une caisse ouverte', async ({ page }) => {
    // Une caisse ouverte par le Caissier
    await seConnecter(page, 'caissier');
    await page.goto('/finances/caisses');
    if ((await page.locator('tbody tr').filter({ hasText: 'Ouverte' }).count()) === 0) {
      await page.locator('[data-bs-target="#caisseModal"]').click();
      const caisse = page.locator('#caisseModal');
      await caisse.locator('[name=libelle]').fill('Caisse boutique');
      await caisse.locator('[name=solde_initial]').fill('0');
      await caisse.locator('button[type=submit]').click();
      await attendreToast(page);
    }

    await seConnecter(page, 'boutique');
    await page.goto('/logistique/reversements');

    await page.locator('#reversement-form [name=magasin_id]').selectOption({ label: 'Boutique scolaire' });
    await page.locator('#reversement-form [name=reference_carnet]').fill('Carnet A-12');

    // Le choix du produit propose son prix de vente
    const ligne = page.locator('#lignes-rev .ligne-form').first();
    await ligne.locator('.ligne-produit').selectOption({ label: PRODUIT });
    await expect(ligne.locator('input[name$="[prix_unitaire]"]')).toHaveValue(/^4000/);
    await ligne.locator('input[name$="[quantite]"]').fill('2');
    await expect(page.locator('#rev-total')).toHaveText(/8\s000,00/);

    // Seules les caisses ouvertes sont proposées
    const caisses = page.locator('#caisse-id option');
    expect(await caisses.count()).toBeGreaterThan(1);
    await page.locator('#caisse-id').selectOption({ index: 1 });
    const nomCaisse = (await page.locator('#caisse-id option:checked').innerText()).trim();

    await page.locator('#rev-declare').fill('8000');
    await page.locator('#btn-valider').click();
    await attendreToast(page, 'Reversement');
    await expect(page).toHaveURL(/\/logistique\/ventes/);

    // Stock : 22 − 2 = 20 ; et le reversement figure au journal de la caisse
    await seConnecter(page, 'achats');
    expect(await stockDe(page, PRODUIT)).toBe(20);

    await seConnecter(page, 'caissier');
    await page.goto('/finances/caisses');
    const caisseOuverte = page.locator('tbody tr').filter({ hasText: nomCaisse }).filter({ hasText: 'Ouverte' });
    await caisseOuverte.locator('.btn-action').click();
    await caisseOuverte.getByRole('link', { name: /journal/i }).click();
    await expect(page.locator('main')).toContainText('Reversement boutique');
    await expect(page.locator('main')).toContainText(/8\s000,00/);
  });

  test('le Responsable Achats & Stock n\'a pas accès au point de vente', async ({ page }) => {
    await seConnecter(page, 'achats');
    for (const chemin of ['/logistique/pos', '/logistique/ventes', '/logistique/reversements']) {
      const reponse = await page.request.get(chemin, { maxRedirects: 0 });
      expect(reponse.status(), chemin).toBe(403);
    }
  });
});
