import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, choisirEleve, expect, seConnecter, test } from '../support/test';

/**
 * Menu 4 — Services aux Élèves › 4.3 Bibliothèque
 * Profil concerné : Bibliothécaire.
 */
test.describe.configure({ mode: 'serial' });

const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

async function ajouterReference(page: Page, onglet: string, type: string, libelle: string): Promise<void> {
  await page.locator(`[data-bs-target="#tab-${onglet}"]`).click();
  await page.locator(`.ref-new-input[data-type="${type}"]`).fill(libelle);
  await page.locator(`.ref-add-btn[data-type="${type}"]`).click();
  await attendreToast(page);
}

test.describe('4.3 Bibliothèque', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'biblio');
  });

  test('gérer les références du catalogue : auteurs, éditeurs, catégories', async ({ page }) => {
    await page.goto('/services-eleves/bibliotheque/referentiels');

    await ajouterReference(page, 'auteurs', 'auteur', 'Camara Laye');
    await ajouterReference(page, 'maisons', 'maison', 'Plon');
    await ajouterReference(page, 'categories', 'categorie', 'Roman');

    await expect(page.locator('#tab-auteurs')).toContainText('Camara Laye');
    await page.locator('[data-bs-target="#tab-maisons"]').click();
    await expect(page.locator('#tab-maisons')).toContainText('Plon');
    await page.locator('[data-bs-target="#tab-categories"]').click();
    await expect(page.locator('#tab-categories')).toContainText('Roman');
  });

  test('ajouter un ouvrage au catalogue (titre, auteur, éditeur, catégorie, exemplaires), puis le modifier', async ({ page }) => {
    await page.goto('/services-eleves/bibliotheque/livres');

    for (const colonne of ['Titre', 'Auteur', 'Catégorie', 'Disponibilité', 'Emprunteur actuel']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    await page.locator('#btnNouveauLivre').click();
    await page.locator('#l-titre').fill('L\'Enfant noir');
    await page.locator('#l-exemplaires').fill('2');
    await page.locator('#l-isbn').fill('978-2-266-02313-0');
    await page.locator('#l-auteur').selectOption({ label: 'Camara Laye' });
    await page.locator('#l-categorie').selectOption({ label: 'Roman' });
    await page.locator('#l-maison').selectOption({ label: 'Plon' });
    await page.locator('#livre-form button[type=submit]').click();
    await attendreToast(page);

    const livre = ligneDe(page, 'L\'Enfant noir');
    await expect(livre).toContainText('Camara Laye');
    await expect(livre).toContainText('Roman');
    await expect(livre).toContainText('Disponible (2/2)');

    await actionDeLigne(page, 'L\'Enfant noir', 'Modifier');
    await expect(page.locator('#l-titre')).toHaveValue('L\'Enfant noir');
    await expect(page.locator('#l-exemplaires')).toHaveValue('2');
    await page.locator('#l-description').fill('Récit autobiographique');
    await page.locator('#livre-form button[type=submit]').click();
    await attendreToast(page);
  });

  test('enregistrer un prêt avec sa date de retour prévue : un exemplaire de moins', async ({ page }) => {
    await page.goto('/services-eleves/bibliotheque/livres');

    const livre = ligneDe(page, 'L\'Enfant noir');
    await livre.locator('.btn-action').click();
    await livre.locator('.btn-pret-livre').click();

    const modale = page.locator('#pretModal');
    await choisirEleve(page, '#pretModal', 'Ama', 'E2E0001');
    await modale.locator('[name=date_pret]').fill('2026-10-01');
    await modale.locator('[name=date_retour_pret]').fill('2026-10-15');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    const emprunte = ligneDe(page, 'L\'Enfant noir');
    await expect(emprunte).toContainText('Dernier exemplaire (1/2)');
    await expect(emprunte).toContainText('KOFFI Ama');
    await expect(emprunte).toContainText('15/10/2026');

    // Liste des prêts en cours
    await page.goto('/services-eleves/bibliotheque/prets');
    for (const colonne of ['Livre', 'Élève', 'Date prêt', 'Retour prévu', 'Retour réel', 'Statut']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    const pret = ligneDe(page, 'L\'Enfant noir');
    await expect(pret).toContainText('KOFFI Ama');
    await expect(pret).toContainText('01/10/2026');
    await expect(pret).toContainText('15/10/2026');
    await expect(pret).toContainText('En cours');
  });

  test('un ouvrage dont tous les exemplaires sont prêtés ne peut plus être prêté', async ({ page }) => {
    await page.goto('/services-eleves/bibliotheque/livres');

    // Second et dernier exemplaire
    let livre = ligneDe(page, 'L\'Enfant noir');
    await livre.locator('.btn-action').click();
    await livre.locator('.btn-pret-livre').click();
    await choisirEleve(page, '#pretModal', 'Kossi', 'E2E0002');
    await page.locator('#pretModal button[type=submit]').click();
    await attendreToast(page);

    livre = ligneDe(page, 'L\'Enfant noir');
    await expect(livre).toContainText('Indisponible (0/2)');
    await livre.locator('.btn-action').click();
    await expect(livre.locator('.btn-pret-livre')).toHaveCount(0);
  });

  test('enregistrer un retour : l\'exemplaire redevient disponible', async ({ page }) => {
    await page.goto('/services-eleves/bibliotheque/prets');

    const pret = ligneDe(page, 'KOFFI Ama');
    await pret.locator('.btn-action').click();
    await pret.locator('.btn-retour').click();
    await page.locator('#retour-form [name=date_retour_reel]').fill('2026-10-10');
    await page.locator('#retour-form [name=observations]').fill('Rendu en bon état');
    await page.locator('#retour-form button[type=submit]').click();
    await attendreToast(page);

    const rendu = ligneDe(page, 'KOFFI Ama');
    await expect(rendu).toContainText('Rendu');
    await expect(rendu).toContainText('10/10/2026');

    await page.goto('/services-eleves/bibliotheque/livres');
    await expect(ligneDe(page, 'L\'Enfant noir')).toContainText('Dernier exemplaire (1/2)');
  });

  test('la fiche d\'un ouvrage retrace l\'historique de ses prêts', async ({ page }) => {
    await page.goto('/services-eleves/bibliotheque/livres');
    await ligneDe(page, 'L\'Enfant noir').locator('a').first().click();

    await expect(page.locator('main')).toContainText('L\'Enfant noir');
    await expect(page.locator('main')).toContainText('Camara Laye');
    await expect(ligneDe(page, 'KOFFI Ama')).toContainText('Rendu');
    await expect(ligneDe(page, 'KOFFI Kossi')).toContainText('En cours');
  });

  test('un ouvrage déclaré perdu sort définitivement du fonds disponible', async ({ page }) => {
    await page.goto('/services-eleves/bibliotheque/prets');

    const pret = ligneDe(page, 'KOFFI Kossi');
    await pret.locator('.btn-action').click();
    await pret.locator('.btn-perdu').click();
    await page.locator('#perdu-form [name=observations]').fill('Livre égaré pendant un déménagement');
    await page.locator('#perdu-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'KOFFI Kossi')).toContainText('Perdu');
  });

  test('le tableau de bord, le recalcul des retards et les exports', async ({ page }) => {
    await page.goto('/services-eleves/bibliotheque/prets');
    await page.locator('form[action*="recalculer-retards"] button').click();
    await attendreToast(page);

    await page.goto('/tableau');
    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Services aux Élèves' }).first();
    await menu.hover();
    const lien = menu.locator('.hnav-drop-item', { hasText: 'Tableau de bord bibliothèque' });
    // Menu long : le groupe ouvre ses écrans dans un second niveau
    const groupe = menu.locator('.hnav-sub').filter({ has: page.locator('.hnav-drop-item', { hasText: 'Tableau de bord bibliothèque' }) });
    if (await groupe.count()) await groupe.hover();
    await lien.click();
    await expect(page.locator('main.content-area')).toBeVisible();
    await expect(page.locator('main')).toContainText('Roman');

    const export_ = await page.request.get('/services-eleves/bibliotheque/prets/export');
    expect(export_.status()).toBe(200);
  });

  test('le Bibliothécaire n\'a accès qu\'à la bibliothèque', async ({ page }) => {
    for (const chemin of ['/scolarite/eleves', '/services-eleves/cantine/inscriptions', '/finances/paiements']) {
      const reponse = await page.request.get(chemin, { maxRedirects: 0 });
      expect(reponse.status(), chemin).toBe(403);
    }
  });
});
