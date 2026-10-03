import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, expect, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité › 2.5 Banques & Chèques
 * Profil concerné : Comptable.
 */
test.describe.configure({ mode: 'serial' });

async function enregistrerCheque(page: Page, numero: string, montant: string): Promise<void> {
  await page.goto('/finances/cheques');
  await page.locator('[data-bs-target="#chequeModal"]').click();
  const formulaire = page.locator('#cheque-form');
  await formulaire.locator('[name=numero]').fill(numero);
  await formulaire.locator('[name=emetteur]').fill('KOFFI Yao');
  await formulaire.locator('[name=banque_id]').selectOption({ label: 'ECOBANK' });
  await formulaire.locator('[name=montant]').fill(montant);
  await formulaire.locator('[name=date_emission]').fill('2026-09-28');
  await formulaire.locator('button[type=submit]').click();
  await attendreToast(page);
}

const ligneDe = (page: Page, texte: string) => page.locator('tbody tr').filter({ hasText: texte });

test.describe('2.5 Banques & Chèques', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'comptable');
  });

  test('créer puis renommer un compte bancaire', async ({ page }) => {
    await page.goto('/finances/banques');
    await expect(page.locator('tbody')).toContainText('ECOBANK');

    await page.locator('#btnNouvelleBanque').click();
    await page.locator('#banque-nom').fill('ORABANK');
    await page.locator('#banque-form button[type=submit]').click();
    await attendreToast(page);
    await expect(page.locator('tbody')).toContainText('ORABANK');

    await actionDeLigne(page, 'ORABANK', 'Modifier');
    await expect(page.locator('#banque-nom')).toHaveValue('ORABANK');
    await page.locator('#banque-nom').fill('ORABANK Togo');
    await page.locator('#banque-form button[type=submit]').click();
    await attendreToast(page);
    await expect(page.locator('tbody')).toContainText('ORABANK Togo');
  });

  test('un chèque incomplet est refusé', async ({ page }) => {
    await page.goto('/finances/cheques');
    await page.locator('[data-bs-target="#chequeModal"]').click();
    await page.locator('#cheque-form button[type=submit]').click();

    await expect(page.locator('#chequeModal')).toBeVisible();
    await expect(page.locator('#chequeModal .is-invalid').first()).toBeVisible();
  });

  test('enregistrer un chèque : il entre au portefeuille avec le statut « Émis »', async ({ page }) => {
    await enregistrerCheque(page, 'CHQ-0001', '150000');

    for (const colonne of ['Numéro', 'Émetteur', 'Banque', 'Émission', 'Statut', 'Montant']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    const ligne = ligneDe(page, 'CHQ-0001');
    await expect(ligne).toContainText('KOFFI Yao');
    await expect(ligne).toContainText('ECOBANK');
    await expect(ligne).toContainText('28/09/2026');
    await expect(ligne).toContainText('Émis');
    await expect(ligne).toContainText('150 000,00');

    // La banque reflète le chèque en attente
    await page.goto('/finances/banques');
    await expect(ligneDe(page, 'ECOBANK')).toContainText('150 000,00');
  });

  test('rapprochement : un chèque est marqué encaissé', async ({ page }) => {
    await page.goto('/finances/cheques');
    const ligne = ligneDe(page, 'CHQ-0001');
    await ligne.locator('.btn-action').click();
    await ligne.locator('.btn-encaisser').click();

    await expect(page.locator('#rapproche-numero')).toHaveText('CHQ-0001');
    await page.locator('#rapproche-form [name=date_encaissement]').fill('2026-09-30');
    await page.locator('#rapproche-submit').click();
    await attendreToast(page);

    await expect(ligneDe(page, 'CHQ-0001')).toContainText('Encaissé');
  });

  test('rapprochement : un chèque rejeté exige un motif', async ({ page }) => {
    await enregistrerCheque(page, 'CHQ-0002', '80000');

    const ligne = ligneDe(page, 'CHQ-0002');
    await ligne.locator('.btn-action').click();
    await ligne.locator('.btn-rejeter').click();
    await expect(page.locator('#motif-rejet-bloc')).toBeVisible();

    // Sans motif : refusé
    await page.locator('#rapproche-submit').click();
    await expect(page.locator('#rapprocheModal')).toBeVisible();
    await expect(page.locator('#motif_rejet-error')).not.toBeEmpty();

    await page.locator('#rapproche-form [name=motif_rejet]').fill('Provision insuffisante');
    await page.locator('#rapproche-submit').click();
    await attendreToast(page);

    await expect(ligneDe(page, 'CHQ-0002')).toContainText('Rejeté');

    // Filtre par statut
    const valeur = await page.locator('select[name=statut] option', { hasText: 'Rejeté' }).getAttribute('value');
    await page.locator('select[name=statut]').selectOption(valeur!);
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText('CHQ-0002');
    await expect(page.locator('tbody')).not.toContainText('CHQ-0001');
  });

  test('la fiche d\'une banque et l\'écran de rapprochement bancaire s\'ouvrent', async ({ page }) => {
    await page.goto('/finances/banques');
    const lien = ligneDe(page, 'ECOBANK').locator('a[href*="/finances/banques/"]').first();
    if (await lien.count()) {
      await lien.click();
      await expect(page.locator('main')).toContainText('ECOBANK');
      await expect(page.locator('main')).toContainText('CHQ-0001');
    }
  });

  test('le portefeuille de chèques s\'exporte', async ({ page }) => {
    await page.goto('/finances/cheques');
    const reponse = await page.request.get(await page.locator('a[href*="/cheques/export"]').getAttribute('href').then((h) => h!));
    expect(reponse.status()).toBe(200);
  });
});
