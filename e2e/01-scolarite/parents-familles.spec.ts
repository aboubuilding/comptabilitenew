import { attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 1 — Scolarité › 1.2 Parents & Familles
 * Profils concernés : Secrétariat (gestion), Direction (consultation).
 */
test.describe('1.2 Parents & Familles', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'secretaire');
  });

  test('la liste affiche le parent principal, ses coordonnées, la fratrie et le compte portail', async ({ page }) => {
    await page.goto('/scolarite/espaces');

    for (const colonne of ['Famille', 'Parent principal', 'Contact', 'Quartier', 'Élèves', 'Compte portail']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    const ligne = page.locator('tbody tr').filter({ hasText: 'Famille KOFFI' });
    await expect(ligne).toContainText('KOFFI');
    await expect(ligne).toContainText('90000001');
    await expect(ligne).toContainText('yao.koffi@exemple.test');
    await expect(ligne).toContainText('2 élèves');
    await expect(ligne).toContainText('Aucun');
  });

  test('créer un espace familial, y ajouter deux parents et désigner le parent principal', async ({ page }) => {
    await page.goto('/scolarite/espaces');

    await page.locator('#btn-nouvel-espace').click();
    await page.locator('#formEspace [name=nom_famille]').fill('Famille MENSAH');
    await page.locator('#formEspace button[type=submit]').click();
    await attendreToast(page);

    await page.getByRole('link', { name: 'Famille MENSAH' }).click();
    await expect(page.locator('.page-title-main')).toContainText('Famille MENSAH');

    // Premier parent
    await page.locator('#btn-nouveau-parent').click();
    let formulaire = page.locator('#formParent');
    await formulaire.locator('[name=nom_parent]').fill('MENSAH');
    await formulaire.locator('[name=prenom_parent]').fill('Kodjo');
    await formulaire.locator('[name=telephone]').fill('90333333');
    await formulaire.locator('[name=email]').fill('kodjo.mensah@exemple.test');
    await formulaire.locator('[name=quartier]').fill('Bè');
    await formulaire.locator('[name=is_principal]').check();
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    // Second parent
    await page.locator('#btn-nouveau-parent').click();
    formulaire = page.locator('#formParent');
    await formulaire.locator('[name=nom_parent]').fill('MENSAH');
    await formulaire.locator('[name=prenom_parent]').fill('Akossiwa');
    await formulaire.locator('[name=telephone]').fill('90333334');
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    const kodjo = page.locator('.border.rounded').filter({ hasText: 'Kodjo' });
    const akossiwa = page.locator('.border.rounded').filter({ hasText: 'Akossiwa' });
    await expect(kodjo).toContainText('Principal');
    await expect(akossiwa).not.toContainText('Principal');

    // Changement de parent principal : un seul principal par famille
    await akossiwa.locator('.btn-action').click();
    await akossiwa.getByRole('button', { name: /Désigner comme principal/ }).click();
    await confirmer(page);
    await attendreToast(page);

    await expect(page.locator('.border.rounded').filter({ hasText: 'Akossiwa' })).toContainText('Principal');
    await expect(page.locator('.border.rounded').filter({ hasText: 'Kodjo' }).locator('.badge', { hasText: 'Principal' })).toHaveCount(0);
  });

  test('créer un compte d\'accès au portail parent', async ({ page }) => {
    await page.goto('/scolarite/espaces');
    await page.getByRole('link', { name: 'Famille KOFFI' }).click();

    await page.locator('#btn-nouveau-compte').click();
    const formulaire = page.locator('#formCompte');
    await formulaire.locator('[name=email]').fill('yao.koffi@exemple.test');
    await formulaire.locator('[name=parent_id]').selectOption({ index: 1 });
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    await expect(page.locator('main')).toContainText('yao.koffi@exemple.test');
    await expect(page.locator('main')).toContainText('Titulaire');

    await page.goto('/scolarite/espaces');
    await expect(page.locator('tbody tr').filter({ hasText: 'Famille KOFFI' })).toContainText('Actif');
  });

  test('regrouper un élève sans famille dans un nouvel espace familial', async ({ page }) => {
    // Un élève créé sans famille
    await page.goto('/scolarite/eleves');
    await page.locator('#btn-nouvel-eleve').click();
    const eleve = page.locator('#formEleve');
    await eleve.locator('[name=nom]').fill('DOSSOU');
    await eleve.locator('[name=prenom]').fill('Yawa');
    await eleve.locator('[name=sexe]').selectOption('2');
    await eleve.locator('[name=date_naissance]').fill('2018-09-09');
    await eleve.locator('button[type=submit]').click();
    await attendreToast(page);

    await page.goto('/scolarite/espaces');
    await page.locator('#btn-regrouper').click();
    const formulaire = page.locator('#formRegrouper');
    await formulaire.locator('#mode-nouveau').check();
    await formulaire.locator('[name=nom_famille]').fill('Famille DOSSOU');
    await formulaire.getByRole('checkbox', { name: /DOSSOU/ }).check();
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    const ligne = page.locator('tbody tr').filter({ hasText: 'Famille DOSSOU' });
    await expect(ligne).toContainText('1 élève');
  });

  test('la liste des familles s\'exporte en Excel', async ({ page }) => {
    await page.goto('/scolarite/espaces');
    const telechargement = page.waitForEvent('download');
    await page.getByRole('link', { name: /Export/i }).click();
    expect((await telechargement).suggestedFilename()).toMatch(/\.xlsx$/);
  });
});
