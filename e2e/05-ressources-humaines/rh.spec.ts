import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 5 — Ressources Humaines :
 *   5.1 Employés (Responsable RH / Paie)
 *   5.2 Paie & Rémunérations (Responsable RH : préparation ; Comptable : règlement ; Direction : consultation)
 *   5.3 Assurances du personnel (Responsable RH / Paie)
 */
test.describe.configure({ mode: 'serial' });

const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

test.describe('5.1 Employés', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'rh');
  });

  test('gérer les professions et leur salaire de base', async ({ page }) => {
    await page.goto('/rh/professions');
    await expect(ligneDe(page, 'Enseignant du primaire')).toContainText(/150\s000/);

    await page.locator('#btnNouvelleProfession').click();
    await page.locator('#pf-libelle').fill('Surveillant');
    await page.locator('#pf-salaire').fill('90000');
    await page.locator('#profession-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Surveillant')).toContainText(/90\s000/);

    await actionDeLigne(page, 'Surveillant', 'Modifier');
    await expect(page.locator('#pf-libelle')).toHaveValue('Surveillant');
    await page.locator('#pf-salaire').fill('95000');
    await page.locator('#profession-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Surveillant')).toContainText(/95\s000/);
  });

  test('créer une fiche employé puis la modifier', async ({ page }) => {
    await page.goto('/rh/employes');

    for (const colonne of ['Employé', 'Profession', 'N° CNSS', 'Téléphone', 'Statut']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    await expect(ligneDe(page, 'AMOUZOU Kossi')).toContainText('Enseignant du primaire');

    await page.locator('#btnNouvelEmploye').click();
    await page.locator('#em-nom').fill('DJOSSOU Afiwa');
    await page.locator('#em-telephone').fill('90999999');
    await page.locator('#em-profession').selectOption({ label: 'Surveillant' });
    await page.locator('#em-salaire').fill('95000');
    await page.locator('#em-date-embauche').fill('2026-09-01');
    await page.locator('#em-cnss').fill('CNSS-445566');
    await page.locator('#em-mode-paiement').selectOption('billette');
    await page.locator('#em-is-cnss').check();
    await page.locator('#employe-form button[type=submit]').click();
    await attendreToast(page);

    const employe = ligneDe(page, 'DJOSSOU Afiwa');
    await expect(employe).toContainText('Surveillant');
    await expect(employe).toContainText('CNSS-445566');
    await expect(employe).toContainText('90999999');
    await expect(employe).toContainText(/95\s000/);
    await expect(employe).toContainText('Actif');

    await actionDeLigne(page, 'DJOSSOU Afiwa', 'Modifier');
    await expect(page.locator('#em-nom')).toHaveValue('DJOSSOU Afiwa');
    await expect(page.locator('#em-date-embauche')).toHaveValue('2026-09-01');
    await expect(page.locator('#em-is-cnss')).toBeChecked();
    await page.locator('#em-telephone').fill('90999990');
    await page.locator('#employe-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'DJOSSOU Afiwa')).toContainText('90999990');
  });

  test('la fiche d\'un employé s\'ouvre ; la liste se filtre par profession et s\'exporte', async ({ page }) => {
    await page.goto('/rh/employes');
    const fiche = ligneDe(page, 'DJOSSOU Afiwa');
    await fiche.locator('.btn-action').click();
    await fiche.locator('a.dropdown-item').first().click();
    await expect(page.locator('main')).toContainText('DJOSSOU Afiwa');
    await expect(page.locator('main')).toContainText('Surveillant');

    await page.goto('/rh/employes');
    const profession = await page.locator('form[method=GET] select[name=profession_id] option', { hasText: 'Surveillant' }).getAttribute('value');
    await page.locator('form[method=GET] select[name=profession_id]').selectOption(profession!);
    await page.locator('form[method=GET] button[type=submit]').first().click();
    await expect(page.locator('tbody')).toContainText('DJOSSOU Afiwa');
    await expect(page.locator('tbody')).not.toContainText('AMOUZOU Kossi');

    expect((await page.request.get('/rh/employes/export')).status()).toBe(200);
  });
});

test.describe('5.2 Paie & Rémunérations', () => {
  test('le Responsable RH définit une période de paie', async ({ page }) => {
    await seConnecter(page, 'rh');
    await page.goto('/rh/periodes');
    await expect(ligneDe(page, 'Septembre 2026')).toContainText('01/09/2026');

    await page.locator('[data-bs-target="#periodeModal"]').click();
    const modale = page.locator('#periodeModal');
    await modale.locator('[name=libelle]').fill('Octobre 2026');
    await modale.locator('[name=date_debut]').fill('2026-10-01');
    await modale.locator('[name=date_fin]').fill('2026-10-31');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    const periode = ligneDe(page, 'Octobre 2026');
    await expect(periode).toContainText('01/10/2026');
    await expect(periode).toContainText('31/10/2026');
    await expect(periode).toContainText('Aucun');
  });

  test('le Responsable RH saisit la rémunération d\'un employé (base, primes, avantages) et ses retenues', async ({ page }) => {
    await seConnecter(page, 'rh');
    await page.goto('/rh/remunerations');

    const employe = await page.locator('select[name=employe_id] option', { hasText: 'DJOSSOU Afiwa' }).getAttribute('value');
    await page.locator('select[name=employe_id]').selectOption(employe!);
    await page.locator('form[method=GET] button[type=submit]').click();

    const remuneration = page.locator('form[action*="remuneration"]').first();
    await remuneration.locator('[name=salaire_base]').fill('95000');
    await remuneration.locator('[name=prime_anciennete]').fill('5000');
    await remuneration.locator('[name=deplacement]').fill('10000');
    await remuneration.locator('button[type=submit]').click();
    await attendreToast(page);

    await expect(page.locator('form[action*="remuneration"] [name=salaire_base]').first()).toHaveValue(/^95000/);
    await expect(page.locator('form[action*="remuneration"] [name=prime_anciennete]').first()).toHaveValue(/^5000/);
  });

  test('générer les bulletins d\'une période : un bulletin par employé, en brouillon', async ({ page }) => {
    await seConnecter(page, 'rh');
    await page.goto('/rh/periodes');

    const periode = ligneDe(page, 'Octobre 2026');
    await periode.locator('.btn-generer').click();
    await page.locator('.swal2-confirm').click();

    // La génération est suivie à l'écran, qui se recharge une fois terminée
    await expect(ligneDe(page, 'Octobre 2026').locator('a[href*="periode_id"]')).toHaveText('2', { timeout: 60_000 });

    await ligneDe(page, 'Octobre 2026').locator('a[href*="periode_id"]').click();
    for (const colonne of ['Référence', 'Employé', 'Période', 'Brut', 'Net à payer', 'Statut']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    const bulletin = ligneDe(page, 'DJOSSOU Afiwa');
    await expect(bulletin).toContainText('Octobre 2026');
    await expect(bulletin).toContainText('Brouillon');
    await expect(ligneDe(page, 'AMOUZOU Kossi')).toContainText('Brouillon');
  });

  test('relancer la génération n\'émet pas de doublon', async ({ page }) => {
    await seConnecter(page, 'rh');
    await page.goto('/rh/periodes');

    await ligneDe(page, 'Octobre 2026').locator('.btn-generer').click();
    await page.locator('.swal2-confirm').click();
    await expect(page.locator('#toast-container .toast-item').last()).toContainText(/ignoré|terminée|bulletin/i, { timeout: 60_000 });
    await page.waitForLoadState('load');

    await page.goto('/rh/periodes');
    await expect(ligneDe(page, 'Octobre 2026').locator('a[href*="periode_id"]')).toHaveText('2');
  });

  test('le bulletin détaille le brut, les retenues et le net ; il s\'édite en PDF', async ({ page }) => {
    await seConnecter(page, 'rh');
    await page.goto('/rh/paies');

    const bulletin = ligneDe(page, 'DJOSSOU Afiwa').first();
    await bulletin.locator('.btn-action').click();
    await bulletin.locator('a[href*="/rh/paies/"]').first().click();
    await expect(page.locator('main')).toContainText('DJOSSOU Afiwa');
    await expect(page.locator('main')).toContainText(/Net à payer/i);
    await expect(page.locator('main')).toContainText(/95\s000/);

    const pdf = await page.request.get(await page.locator('a[href*="export-pdf"], a[href*="exportPdf"], a[href$="/pdf"]').first().getAttribute('href').then((h) => h!));
    expect(pdf.status()).toBe(200);
    expect(pdf.headers()['content-type']).toContain('pdf');
  });

  test('le Responsable RH valide le bulletin mais ne le règle pas', async ({ page }) => {
    await seConnecter(page, 'rh');
    await page.goto('/rh/paies');

    const bulletin = ligneDe(page, 'DJOSSOU Afiwa').first();
    await bulletin.locator('.btn-action').click();
    await expect(bulletin.getByRole('button', { name: 'Marquer payé' })).toHaveCount(0);
    await bulletin.getByRole('button', { name: 'Valider' }).click();
    await confirmer(page);
    await attendreToast(page);

    await expect(ligneDe(page, 'DJOSSOU Afiwa').first()).toContainText('Validé');
  });

  test('le Comptable règle le bulletin, puis confirme le retrait en billetterie', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/rh/paies');

    let bulletin = ligneDe(page, 'DJOSSOU Afiwa').first();
    await bulletin.locator('.btn-action').click();
    // Le Comptable règle : il ne valide ni ne génère
    await expect(bulletin.getByRole('button', { name: 'Valider' })).toHaveCount(0);
    await expect(page.locator('[data-bs-target="#paieModal"]')).toHaveCount(0);
    await bulletin.getByRole('button', { name: 'Marquer payé' }).click();
    await confirmer(page);
    await attendreToast(page);

    bulletin = ligneDe(page, 'DJOSSOU Afiwa').first();
    await expect(bulletin).toContainText('Payé');

    await bulletin.locator('.btn-action').click();
    await bulletin.getByRole('button', { name: /Confirmer le retrait/ }).click();
    await confirmer(page);
    await attendreToast(page);
  });

  test('la Direction consulte la paie et son tableau de bord, sans action possible', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/rh/paies');

    const bulletin = ligneDe(page, 'DJOSSOU Afiwa').first();
    await expect(bulletin).toContainText('Payé');
    await bulletin.locator('.btn-action').click();
    await expect(bulletin.locator('form')).toHaveCount(0);
    await expect(page.locator('[data-bs-target="#paieModal"]')).toHaveCount(0);

    await page.getByRole('link', { name: /Tableau de bord|Dashboard/i }).first().click();
    await expect(page.locator('main.content-area')).toBeVisible();

    expect((await page.request.get('/rh/paies/export')).status()).toBe(200);

    // La Direction ne voit pas les fiches du personnel
    const refus = await page.request.get('/rh/employes', { maxRedirects: 0 });
    expect(refus.status()).toBe(403);
  });
});

test.describe('5.3 Assurances du personnel', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'rh');
  });

  test('enregistrer une police d\'assurance', async ({ page }) => {
    await page.goto('/rh/asurances');

    await page.locator('#btn-nouvelle-asurance').click();
    const formulaire = page.locator('#formAsurance');
    await formulaire.locator('[name=nom_assureur]').fill('SUNU Assurances');
    await formulaire.locator('[name=police_assurance]').fill('POL-2026-001');
    await formulaire.locator('[name=montant_annuel]').fill('600000');
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    const police = ligneDe(page, 'SUNU Assurances');
    await expect(police).toContainText('POL-2026-001');
    await expect(police).toContainText(/600\s000/);
  });

  test('souscrire une assurance pour un employé, avec son prélèvement mensuel', async ({ page }) => {
    await page.goto('/rh/assures');

    for (const colonne of ['Employé', 'Assureur', 'N° de police', 'Prélèvement mensuel', 'Statut']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    await page.locator('#btn-nouvelle-assurance').click();
    const formulaire = page.locator('#formAssure');
    const employe = await formulaire.locator('[name=employe_id] option', { hasText: 'DJOSSOU Afiwa' }).getAttribute('value');
    await formulaire.locator('[name=employe_id]').selectOption(employe!);
    const police = await formulaire.locator('[name=assurance_id] option', { hasText: 'SUNU' }).getAttribute('value');
    await formulaire.locator('[name=assurance_id]').selectOption(police!);
    await formulaire.locator('[name=date_souscription]').fill('2026-10-01');
    await formulaire.locator('[name=prelevement_mensuel]').fill('5000');
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    const assure = ligneDe(page, 'DJOSSOU Afiwa');
    await expect(assure).toContainText('SUNU Assurances');
    await expect(assure).toContainText('POL-2026-001');
    await expect(assure).toContainText(/5\s000/);

    // La police compte un assuré
    await page.goto('/rh/asurances');
    await expect(ligneDe(page, 'SUNU Assurances')).toContainText('1');

    expect((await page.request.get('/rh/assures/export')).status()).toBe(200);
  });

  test('un employé ne peut pas être assuré deux fois par la même police', async ({ page }) => {
    await page.goto('/rh/assures');
    await page.locator('#btn-nouvelle-assurance').click();
    const formulaire = page.locator('#formAssure');
    const employe = await formulaire.locator('[name=employe_id] option', { hasText: 'DJOSSOU Afiwa' }).getAttribute('value');
    await formulaire.locator('[name=employe_id]').selectOption(employe!);
    const police = await formulaire.locator('[name=assurance_id] option', { hasText: 'SUNU' }).getAttribute('value');
    await formulaire.locator('[name=assurance_id]').selectOption(police!);
    await formulaire.locator('[name=prelevement_mensuel]').fill('5000');
    await formulaire.locator('button[type=submit]').click();

    await expect(page.locator('#modalAssure .is-invalid, #modalAssure .invalid-feedback, #toast-container .toast-error').first()).toBeVisible();
    await page.goto('/rh/assures');
    await expect(ligneDe(page, 'DJOSSOU Afiwa')).toHaveCount(1);
  });

  test('les autres profils n\'ont pas accès aux dossiers du personnel', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    for (const chemin of ['/rh/employes', '/rh/paies', '/rh/assures']) {
      const reponse = await page.request.get(chemin, { maxRedirects: 0 });
      expect(reponse.status(), chemin).toBe(403);
    }
  });
});
