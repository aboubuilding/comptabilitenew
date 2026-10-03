import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 1 — Scolarité › 1.4 Structure pédagogique & Années scolaires
 * Profil concerné : Administrateur.
 */
test.describe('1.4 Structure pédagogique & Années scolaires', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'admin');
  });

  test('la liste des années affiche le libellé, les dates clés et le statut', async ({ page }) => {
    await page.goto('/scolarite/annees');

    const ligne = page.locator('tbody tr').filter({ hasText: '2026-2027' });
    await expect(ligne).toContainText('15/09/2026');
    await expect(ligne).toContainText('15/07/2027');
    await expect(ligne).toContainText('01/06/2026');
    await expect(ligne).toContainText('Active');
  });

  test('une année sans libellé ni dates est refusée, champ par champ', async ({ page }) => {
    await page.goto('/scolarite/annees');
    await page.locator('#btnNouvelleAnnee').click();
    await page.locator('#annee-form button[type=submit]').click();

    await expect(page.locator('#annee-libelle-error')).not.toBeEmpty();
    await expect(page.locator('#annee-date_rentree-error')).not.toBeEmpty();
    await expect(page.locator('#anneeModal')).toBeVisible();
  });

  test('créer, modifier, ouvrir puis clôturer une année scolaire', async ({ page }) => {
    await page.goto('/scolarite/annees');

    await page.locator('#btnNouvelleAnnee').click();
    await page.locator('#annee-libelle').fill('2027-2028');
    await page.locator('#annee-date-rentree').fill('2027-09-15');
    await page.locator('#annee-date-fin').fill('2028-07-15');
    await page.locator('#annee-date-ouverture').fill('2027-06-01');
    await page.locator('#annee-date-fermeture').fill('2028-01-31');
    await page.locator('#annee-form button[type=submit]').click();
    await attendreToast(page, 'créée');

    // Une année se prépare à l'avance : elle n'est pas active tant qu'elle n'est pas ouverte
    const ligne = page.locator('tbody tr').filter({ hasText: '2027-2028' });
    await expect(ligne).toContainText('15/09/2027');
    await expect(ligne).toContainText('Non ouverte');

    // Modification : la modale est pré-remplie avec les valeurs enregistrées
    await actionDeLigne(page, '2027-2028', 'Modifier');
    await expect(page.locator('#annee-libelle')).toHaveValue('2027-2028');
    await expect(page.locator('#annee-date-rentree')).toHaveValue('2027-09-15');
    await page.locator('#annee-date-fin').fill('2028-07-31');
    await page.locator('#annee-form button[type=submit]').click();
    await attendreToast(page, 'modifiée');
    await expect(page.locator('tbody tr').filter({ hasText: '2027-2028' })).toContainText('31/07/2028');

    // Ouverture
    await actionDeLigne(page, '2027-2028', 'Ouvrir');
    await confirmer(page);
    await attendreToast(page, 'ouverte');
    await expect(page.locator('tbody tr').filter({ hasText: '2027-2028' })).toContainText('Active');

    // Clôture
    await actionDeLigne(page, '2027-2028', 'Clôturer');
    await confirmer(page);
    await attendreToast(page, 'clôturée');
    const cloturee = page.locator('tbody tr').filter({ hasText: '2027-2028' });
    await expect(cloturee).toContainText('Clôturée');

    // Une année clôturée ne se rouvre pas et ne se reclôture pas
    await cloturee.locator('.btn-action').click();
    await expect(cloturee.getByRole('button', { name: 'Ouvrir' })).toHaveCount(0);
    await expect(cloturee.getByRole('button', { name: 'Clôturer' })).toHaveCount(0);
  });

  test('créer, modifier et désactiver un cycle', async ({ page }) => {
    await page.goto('/scolarite/cycles');
    await expect(page.locator('tbody')).toContainText('Primaire');

    await page.locator('#btnNouveau').click();
    await page.locator('#cycle-libelle').fill('Collège');
    await page.locator('#cycle-form button[type=submit]').click();
    await attendreToast(page);
    await expect(page.locator('tbody')).toContainText('Collège');

    await actionDeLigne(page, 'Collège', 'Modifier');
    await expect(page.locator('#cycle-libelle')).toHaveValue('Collège');
    await page.locator('#cycle-libelle').fill('Collège général');
    await page.locator('#cycle-form button[type=submit]').click();
    await attendreToast(page);
    await expect(page.locator('tbody')).toContainText('Collège général');

    // Archivage logique (§ 6 « Statuts et états génériques ») : le cycle quitte la liste
    await page.locator('tbody tr').filter({ hasText: 'Collège général' }).locator('.btn-action').click();
    await page.locator('tbody tr').filter({ hasText: 'Collège général' }).locator('form button[type=submit]').click();
    await confirmer(page);
    await attendreToast(page);
    await expect(page.locator('tbody')).not.toContainText('Collège général');
  });

  test('créer un niveau rattaché à un cycle, puis filtrer la liste par cycle', async ({ page }) => {
    await page.goto('/scolarite/niveaux');
    await expect(page.locator('tbody')).toContainText('CP');

    await page.locator('#btnNouveauNiveau').click();
    await page.locator('#niveau-libelle').fill('CE2');
    await page.locator('#niveau-cycle').selectOption({ label: 'Primaire' });
    await page.locator('#niveau-ordre').fill('3');
    await page.locator('#niveau-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = page.locator('tbody tr').filter({ hasText: 'CE2' });
    await expect(ligne).toContainText('Primaire');

    await page.locator('form[method=GET] select[name=cycle_id]').selectOption({ label: 'Primaire' });
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page).toHaveURL(/cycle_id=\d+/);
    await expect(page.locator('tbody')).toContainText('CE2');
  });

  test('un niveau sans libellé est refusé', async ({ page }) => {
    await page.goto('/scolarite/niveaux');
    await page.locator('#btnNouveauNiveau').click();
    await page.locator('#niveau-form button[type=submit]').click();

    await expect(page.locator('#niveauModal')).toBeVisible();
    await expect(page.locator('#niveauModal .is-invalid').first()).toBeVisible();
  });
});
