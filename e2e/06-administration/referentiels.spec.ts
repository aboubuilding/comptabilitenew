import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 6 — Administration › 6.2 Référentiels & Paramètres généraux
 * Profil concerné : Administrateur.
 */
test.describe.configure({ mode: 'serial' });

const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

test.describe('6.2 Référentiels & Paramètres généraux', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'admin');
  });

  test('la liste affiche le libellé, le type de référentiel et le statut', async ({ page }) => {
    await page.goto('/tableau');
    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Administration' }).first();
    await menu.hover();
    const lien = menu.locator('.hnav-drop-item', { hasText: 'Nationalités' });
    // Menu long : le groupe ouvre ses écrans dans un second niveau
    const groupe = menu.locator('.hnav-sub').filter({ has: page.locator('.hnav-drop-item', { hasText: 'Nationalités' }) });
    if (await groupe.count()) await groupe.hover();
    await lien.click();
    await expect(page).toHaveURL(/\/administration\/nationalites$/);

    for (const colonne of ['Libellé', 'Type de référentiel', 'Statut']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    const togolaise = ligneDe(page, 'Togolaise');
    await expect(togolaise).toContainText('Nationalité');
    await expect(togolaise).toContainText('Actif');
  });

  test('créer puis modifier une valeur de référence', async ({ page }) => {
    await page.goto('/administration/nationalites');

    await page.locator('#btnNouvelleNationalite').click();
    await page.locator('#nationalite-libelle').fill('Béninoise');
    await page.locator('#nationalite-form button[type=submit]').click();
    await attendreToast(page, 'ajoutée');
    await expect(ligneDe(page, 'Béninoise')).toBeVisible();

    await actionDeLigne(page, 'Béninoise', 'Modifier');
    await expect(page.locator('#nationalite-libelle')).toHaveValue('Béninoise');
    await page.locator('#nationalite-libelle').fill('Béninoise (Bénin)');
    await page.locator('#nationalite-form button[type=submit]').click();
    await attendreToast(page, 'modifiée');
    await expect(ligneDe(page, 'Béninoise (Bénin)')).toBeVisible();
  });

  test('un doublon et un libellé vide sont refusés', async ({ page }) => {
    await page.goto('/administration/nationalites');

    await page.locator('#btnNouvelleNationalite').click();
    await page.locator('#nationalite-form button[type=submit]').click();
    await expect(page.locator('#nationalite-libelle-error')).toContainText('obligatoire');

    await page.locator('#nationalite-libelle').fill('Togolaise');
    await page.locator('#nationalite-form button[type=submit]').click();
    await expect(page.locator('#nationalite-libelle-error')).toContainText('existe déjà');
    await expect(page.locator('#nationaliteModal')).toBeVisible();
  });

  test('la nouvelle valeur est proposée dans la fiche élève', async ({ page }) => {
    await page.goto('/scolarite/eleves');
    await page.locator('#btn-nouvel-eleve').click();
    await expect(page.locator('#formEleve [name=nationalite_id] option', { hasText: 'Béninoise (Bénin)' })).toHaveCount(1);
  });

  test('une valeur inutilisée se désactive ; une valeur attribuée à des élèves, non', async ({ page }) => {
    await page.goto('/administration/nationalites');

    const togolaise = ligneDe(page, 'Togolaise');
    await togolaise.locator('.btn-action').click();
    await togolaise.locator('form button[type=submit]').click();
    await confirmer(page);
    await expect(page.locator('#toast-container .toast-error')).toContainText('attribuée à des élèves');
    await expect(ligneDe(page, 'Togolaise')).toHaveCount(1);

    const beninoise = ligneDe(page, 'Béninoise');
    await beninoise.locator('.btn-action').click();
    await beninoise.locator('form button[type=submit]').click();
    await confirmer(page);
    await attendreToast(page, 'désactivée');
    await expect(page.locator('tbody')).not.toContainText('Béninoise');
  });

  test('les référentiels sont réservés à l\'Administrateur', async ({ page }) => {
    for (const profil of ['directeur', 'secretaire', 'adjoint'] as const) {
      await seConnecter(page, profil);
      const reponse = await page.request.get('/administration/nationalites', { maxRedirects: 0 });
      expect(reponse.status(), profil).toBe(403);
    }
  });
});
