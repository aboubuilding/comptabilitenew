import { COMPTES, MOT_DE_PASSE } from '../support/comptes';
import { expect, seConnecter, test } from '../support/test';

/**
 * Accès à l'application — cahier des charges § 3 (profils) et § 6.4 (dernière connexion).
 */
test.describe('Connexion', () => {
  test('un visiteur non connecté est renvoyé vers la page de connexion', async ({ page }) => {
    await page.goto('/tableau');
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('#form-login')).toBeVisible();
  });

  test('un identifiant inconnu est refusé avec un message explicite', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#login').fill('personne');
    await page.locator('#mot_passe').fill('peu-importe');
    await page.locator('#btn-login').click();

    await expect(page.locator('#alert-banner')).toBeVisible();
    await expect(page.locator('#alert-banner-title')).toHaveText('Identifiant introuvable');
    await expect(page).toHaveURL(/\/login$/);
  });

  test('un mot de passe erroné est refusé avec un message explicite', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#login').fill(COMPTES.comptable);
    await page.locator('#mot_passe').fill('mauvais-mot-de-passe');
    await page.locator('#btn-login').click();

    await expect(page.locator('#alert-banner-title')).toHaveText('Mot de passe incorrect');
    await expect(page).toHaveURL(/\/login$/);
  });

  test('les champs vides sont signalés sans appel au serveur', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#btn-login').click();

    await expect(page.locator('#error-login')).toBeVisible();
    await expect(page.locator('#error-motpasse')).toBeVisible();
  });

  test('un utilisateur se connecte par le formulaire, voit son profil, puis se déconnecte', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#login').fill(COMPTES.comptable);
    await page.locator('#mot_passe').fill(MOT_DE_PASSE);
    await page.locator('#btn-login').click();

    await expect(page).toHaveURL(/\/tableau$/);
    await expect(page.locator('.hbtp-avatar-role').first()).toHaveText('Comptable');

    await page.locator('#hbtp-avatar-btn').click();
    await page.getByRole('menuitem', { name: 'Déconnexion' }).click();

    await expect(page).toHaveURL(/\/login$/);
    await page.goto('/tableau');
    await expect(page).toHaveURL(/\/login$/);
  });

  test('un utilisateur déjà connecté ne revoit pas la page de connexion', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/login');
    await expect(page).toHaveURL(/\/tableau$/);
  });

  test('la connexion enregistre la date et l\'adresse de dernière connexion (§ 6.4)', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await seConnecter(page, 'admin');

    await page.goto('/administration/users');
    const ligne = page.locator('tbody tr').filter({ hasText: COMPTES.directeur });
    await expect(ligne).toBeVisible();
    await expect(ligne).not.toContainText('Jamais');
  });
});
