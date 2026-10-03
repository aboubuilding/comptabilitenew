import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité › 2.1 Frais scolaires & Échéanciers
 * Profils concernés : Comptable (gestion), Direction (consultation).
 */
test.describe('2.1 Frais scolaires & Échéanciers', () => {
  test('la liste affiche le libellé, le niveau, le montant, le type de paiement et le plan associé', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles');

    for (const colonne of ['Libellé', 'Niveau', 'Montant', 'Type de paiement', 'Plan d\'échéancier']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    const ligne = page.locator('tbody tr').filter({ hasText: 'Scolarité CP' });
    await expect(ligne).toContainText('CP');
    await expect(ligne).toContainText('469 000');
    await expect(ligne).toContainText('Comptant');
  });

  test('le Comptable crée un plan d\'échéancier', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/plan-echeanciers');

    await page.locator('#btnNouveauPlan').click();
    await page.locator('#plan-nom').fill('3 tranches trimestrielles');
    await page.locator('#plan-description').fill('Octobre, janvier, avril');

    // Trois échéances : 40 % + 30 % + 20 % = 90 %, refusé tant que le total n'est pas 100 %
    const echeances = [
      ['1re tranche', '2026-10-15', '40'],
      ['2e tranche', '2027-01-15', '30'],
      ['3e tranche', '2027-04-15', '20'],
    ];
    for (let i = 0; i < echeances.length; i++) {
      if (i > 0) await page.locator('#plan-ajouter-ligne').click();
      const ligne = page.locator('#plan-lignes tbody tr').nth(i);
      await ligne.locator('.plan-ligne-libelle').fill(echeances[i][0]);
      await ligne.locator('.plan-ligne-date').fill(echeances[i][1]);
      await ligne.locator('.plan-ligne-part').fill(echeances[i][2]);
    }
    await expect(page.locator('#plan-total')).toHaveText('90 %');
    await page.locator('#plan-enregistrer').click();
    await expect(page.locator('#plan-lignes-error')).toContainText('100 %');
    await expect(page.locator('#planModal')).toBeVisible();

    await page.locator('#plan-lignes tbody tr').nth(2).locator('.plan-ligne-part').fill('30');
    await expect(page.locator('#plan-total')).toHaveText('100 %');
    await page.locator('#plan-enregistrer').click();
    await attendreToast(page);

    const plan = page.locator('tbody tr').filter({ hasText: '3 tranches trimestrielles' });
    await expect(plan).toContainText('Octobre, janvier, avril');
    await expect(plan.locator('.badge-lignes-count')).toHaveText('3');

    // La modification recharge le plan et ses échéances
    await plan.locator('.btn-action').click();
    await plan.locator('.btn-edit-plan').click();
    await expect(page.locator('#plan-nom')).toHaveValue('3 tranches trimestrielles');
    await expect(page.locator('#plan-lignes tbody tr')).toHaveCount(3);
    await expect(page.locator('#plan-lignes tbody tr').first().locator('.plan-ligne-libelle')).toHaveValue('1re tranche');
    await expect(page.locator('#plan-lignes tbody tr').first().locator('.plan-ligne-date')).toHaveValue('2026-10-15');

    // Retrait d'une échéance et rééquilibrage : 40 % + 60 %
    await page.locator('#plan-lignes tbody tr').nth(2).locator('.plan-ligne-retirer').click();
    await page.locator('#plan-lignes tbody tr').nth(1).locator('.plan-ligne-part').fill('60');
    await page.locator('#plan-enregistrer').click();
    await attendreToast(page);
    await expect(page.locator('tbody tr').filter({ hasText: '3 tranches trimestrielles' }).locator('.badge-lignes-count')).toHaveText('2');
  });

  test('un plan sans échéance est refusé', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/plan-echeanciers');

    await page.locator('#btnNouveauPlan').click();
    await page.locator('#plan-nom').fill('Plan vide');
    await page.locator('#plan-lignes tbody tr .plan-ligne-retirer').click();
    await page.locator('#plan-enregistrer').click();

    await expect(page.locator('#plan-lignes-error')).toContainText('au moins une échéance');
    await expect(page.locator('#planModal')).toBeVisible();
  });

  test('le Comptable définit un frais échelonné rattaché à un plan, puis le modifie', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles');

    await page.locator('#btnNouveauFrais').click();
    await page.locator('#frais-libelle').fill('Scolarité CE1');
    await page.locator('#frais-niveau').selectOption({ label: 'CE1' });
    await page.locator('#frais-montant').fill('480000');
    const scolarite = await page.locator('#frais-type-paiement option', { hasText: /scola/i }).first().getAttribute('value');
    await page.locator('#frais-type-paiement').selectOption(scolarite!);
    const echelonne = await page.locator('#frais-modalite-paiement option', { hasText: /chelonn/i }).first().getAttribute('value');
    await page.locator('#frais-modalite-paiement').selectOption(echelonne!);
    const plan = await page.locator('#frais-plan option', { hasText: '3 tranches trimestrielles' }).getAttribute('value');
    await page.locator('#frais-plan').selectOption(plan!);
    await page.locator('#frais-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = page.locator('tbody tr').filter({ hasText: 'Scolarité CE1' });
    await expect(ligne).toContainText('480 000');
    await expect(ligne).toContainText(/chelonn/i);
    await expect(ligne).toContainText('3 tranches trimestrielles');

    await actionDeLigne(page, 'Scolarité CE1', 'Modifier');
    await expect(page.locator('#frais-libelle')).toHaveValue('Scolarité CE1');
    await expect(page.locator('#frais-montant')).toHaveValue(/^480000/);
    await page.locator('#frais-montant').fill('485000');
    await page.locator('#frais-form button[type=submit]').click();
    await attendreToast(page);
    await expect(page.locator('tbody tr').filter({ hasText: 'Scolarité CE1' })).toContainText('485 000');
  });

  test('un frais sans libellé ni montant est refusé', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles');
    await page.locator('#btnNouveauFrais').click();
    await page.locator('#frais-form button[type=submit]').click();

    await expect(page.locator('#fraisModal')).toBeVisible();
    await expect(page.locator('#fraisModal .is-invalid, #toast-container .toast-error').first()).toBeVisible();
  });

  test('le Comptable crée une tranche de paiement sur un frais', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/tranches');

    await page.locator('#btnNouvelleTranche').click();
    await page.locator('#tranche-libelle').fill('1re tranche CE1');
    const frais = await page.locator('#tranche-frais option', { hasText: 'Scolarité CE1' }).getAttribute('value');
    await page.locator('#tranche-frais').selectOption(frais!);
    await page.locator('#tranche-taux').fill('40');
    await page.locator('#tranche-date').fill('2026-10-31');
    await page.locator('#tranche-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = page.locator('tbody tr').filter({ hasText: '1re tranche CE1' });
    await expect(ligne).toContainText('Scolarité CE1');
    await expect(ligne).toContainText('40');
    await expect(ligne).toContainText('31/10/2026');
  });

  test('la fiche d\'un frais liste ses souscriptions ; la liste s\'exporte', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles');

    const ligne = page.locator('tbody tr').filter({ hasText: 'Cantine CP' });
    await ligne.locator('.btn-action').click();
    await ligne.locator('a.dropdown-item').first().click();
    await expect(page.locator('.page-title-main')).toContainText('Détail du frais');
    await expect(page.locator('main')).toContainText('Cantine CP');
    await expect(page.locator('main')).toContainText('108 000');

    await page.goto('/finances/frais-ecoles');
    const pdf = await page.request.get(await page.locator('a[href*="export"]').first().getAttribute('href').then((h) => h!));
    expect(pdf.status()).toBe(200);
  });

  test('le Comptable désactive un frais : il quitte la liste', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles');

    await page.locator('#btnNouveauFrais').click();
    await page.locator('#frais-libelle').fill('Frais à retirer');
    await page.locator('#frais-niveau').selectOption({ label: 'CP' });
    await page.locator('#frais-montant').fill('1000');
    await page.locator('#frais-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = page.locator('tbody tr').filter({ hasText: 'Frais à retirer' });
    await ligne.locator('.btn-action').click();
    await ligne.locator('form button[type=submit]').click();
    await confirmer(page);
    await attendreToast(page);
    await expect(page.locator('tbody')).not.toContainText('Frais à retirer');
  });

  test('la Direction consulte les frais sans pouvoir les modifier', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/finances/frais-ecoles');

    await expect(page.locator('tbody')).toContainText('Scolarité CP');
    await expect(page.locator('#btnNouveauFrais')).toHaveCount(0);
    await expect(page.locator('.btn-edit-frais')).toHaveCount(0);

    // Elle consulte les échéances d'un plan sans pouvoir les modifier
    await page.goto('/finances/plan-echeanciers');
    await expect(page.locator('#btnNouveauPlan')).toHaveCount(0);
    const plan = page.locator('tbody tr').filter({ hasText: '3 tranches trimestrielles' });
    await plan.locator('.btn-action').click();
    await plan.locator('.btn-edit-plan').click();
    await expect(page.locator('#plan-lignes tbody tr')).toHaveCount(2);
    await expect(page.locator('#plan-enregistrer')).toBeHidden();
    await expect(page.locator('#plan-ajouter-ligne')).toBeHidden();
  });
});
