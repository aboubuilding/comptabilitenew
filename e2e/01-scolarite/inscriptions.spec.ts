import type { Page } from '@playwright/test';
import { attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 1 — Scolarité › 1.3 Inscriptions, et règle transversale
 * « Cycle de vie d'une inscription » (§ 6) : saisie par le secrétariat,
 * validation ou rejet motivé par la Direction, abandon en cours d'année.
 */
async function creerEleve(page: Page, nom: string, prenom: string): Promise<void> {
  await page.goto('/scolarite/eleves');
  await page.locator('#btn-nouvel-eleve').click();
  const formulaire = page.locator('#formEleve');
  await formulaire.locator('[name=nom]').fill(nom);
  await formulaire.locator('[name=prenom]').fill(prenom);
  await formulaire.locator('[name=sexe]').selectOption('1');
  await formulaire.locator('[name=date_naissance]').fill('2019-01-15');
  await formulaire.locator('button[type=submit]').click();
  await attendreToast(page);
}

async function saisirInscription(page: Page, eleve: string): Promise<void> {
  await page.goto('/scolarite/inscriptions');
  await page.locator('#btnNouvelleInscription').click();

  const modale = page.locator('#inscriptionModal');
  await modale.locator('.select2-selection').click();
  await page.locator('.select2-search__field').fill(eleve);
  await page.locator('.select2-results__option', { hasText: eleve }).first().click();

  await modale.locator('[name=cycle_id]').selectOption({ label: 'Primaire' });
  await modale.locator('[name=niveau_id]').selectOption({ label: 'CE1' });
  await modale.locator('[name=classe_id]').selectOption({ label: 'CE1 A' });
  await modale.locator('[name=frais_scolarite]').fill('469000');
  await modale.locator('[name=taux_remise]').fill('10');
  await modale.locator('button[type=submit]').click();
  await attendreToast(page);
}

const ligneDe = (page: Page, eleve: string) => page.locator('tbody tr').filter({ hasText: eleve });

test.describe('1.3 Inscriptions', () => {
  test('la liste affiche l\'élève, la classe, la date, le type, le statut, les frais et la remise', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/inscriptions');

    for (const colonne of ['Élève', 'Classe', 'Date', 'Type', 'Statut', 'Frais scolarité', 'Remise']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    const ligne = ligneDe(page, 'E2E0001');
    await expect(ligne).toContainText('KOFFI Ama');
    await expect(ligne).toContainText('CP A');
    await expect(ligne).toContainText('Validée');
  });

  test('une inscription sans élève est refusée', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/inscriptions');
    await page.locator('#btnNouvelleInscription').click();
    await page.locator('#inscription-form button[type=submit]').click();

    await expect(page.locator('#insc-eleve_id-error')).not.toBeEmpty();
    await expect(page.locator('#inscriptionModal')).toBeVisible();
  });

  test('le secrétariat saisit une inscription, la Direction la valide, puis un abandon est enregistré', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await creerEleve(page, 'AGBEKO', 'Edem');
    await saisirInscription(page, 'AGBEKO');

    // Saisie : en attente de validation, le secrétariat ne peut pas valider lui-même
    let ligne = ligneDe(page, 'AGBEKO');
    await expect(ligne).toContainText('En attente');
    await expect(ligne).toContainText('CE1 A');
    await expect(ligne).toContainText('469 000');
    await expect(ligne).toContainText('10 %');

    // Validation par la Direction
    await seConnecter(page, 'directeur');
    await page.goto('/scolarite/inscriptions');
    ligne = ligneDe(page, 'AGBEKO');
    await ligne.locator('.btn-action').click();
    await ligne.getByRole('button', { name: 'Valider' }).click();
    await confirmer(page);
    await attendreToast(page);
    await expect(ligneDe(page, 'AGBEKO')).toContainText('Validée');

    // Abandon en cours d'année, avec motif
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/inscriptions');
    ligne = ligneDe(page, 'AGBEKO');
    await ligne.locator('.btn-action').click();
    await ligne.getByRole('button', { name: /abandon/i }).click();
    await expect(page.locator('#motif-modal-title')).toHaveText('Motif de l\'abandon');
    await page.locator('#motif-textarea').fill('Déménagement de la famille');
    await page.locator('#motif-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'AGBEKO')).toContainText('Abandonnée');
  });

  test('la Direction rejette une inscription en indiquant le motif', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await creerEleve(page, 'LAWSON', 'Folly');
    await saisirInscription(page, 'LAWSON');

    await seConnecter(page, 'directeur');
    await page.goto('/scolarite/inscriptions');
    const ligne = ligneDe(page, 'LAWSON');
    await ligne.locator('.btn-action').click();
    await ligne.getByRole('button', { name: 'Rejeter' }).click();
    await expect(page.locator('#motif-modal-title')).toHaveText('Motif du rejet');
    await page.locator('#motif-textarea').fill('Dossier incomplet');
    await page.locator('#motif-form button[type=submit]').click();
    await attendreToast(page);

    await expect(ligneDe(page, 'LAWSON')).toContainText('Rejetée');

    // Le filtre par statut retrouve le dossier rejeté
    await page.locator('form[method=GET] select[name=statut]').selectOption({ label: 'Rejetée' });
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText('LAWSON');
    await expect(page.locator('tbody')).not.toContainText('E2E0001');
  });

  test('un élève ne peut pas être inscrit deux fois la même année', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/inscriptions');
    await page.locator('#btnNouvelleInscription').click();

    const modale = page.locator('#inscriptionModal');
    await modale.locator('.select2-selection').click();
    await page.locator('.select2-search__field').fill('E2E0001');
    await page.locator('.select2-results__option', { hasText: 'E2E0001' }).first().click();
    await modale.locator('[name=cycle_id]').selectOption({ label: 'Primaire' });
    await modale.locator('[name=niveau_id]').selectOption({ label: 'CP' });
    await modale.locator('button[type=submit]').click();

    await expect(page.locator('#insc-eleve_id-error')).toContainText('déjà une inscription');
    await expect(modale).toBeVisible();

    await page.reload();
    await expect(page.locator('tbody tr').filter({ hasText: 'E2E0001' })).toHaveCount(1);
  });

  test('le secrétariat ne peut pas valider, le Comptable ne fait que consulter', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await creerEleve(page, 'TOGBE', 'Mawuli');
    await saisirInscription(page, 'TOGBE');

    // Le secrétariat ne se voit proposer ni validation ni rejet : pour un dossier
    // en attente il n'a aucune action, le menu « … » n'est donc pas affiché.
    let ligne = ligneDe(page, 'TOGBE');
    await expect(ligne).toContainText('En attente');
    await expect(ligne.locator('.btn-action')).toBeHidden();
    await expect(ligne.getByRole('button', { name: 'Valider' })).toHaveCount(0);
    await expect(ligne.getByRole('button', { name: 'Rejeter' })).toHaveCount(0);

    // L'adresse de validation, telle que la Direction la voit
    await seConnecter(page, 'directeur');
    await page.goto('/scolarite/inscriptions');
    const url = await ligneDe(page, 'TOGBE').locator('form[action*="/valider"]').getAttribute('action');
    expect(url).toBeTruthy();

    // ... est refusée au secrétariat comme au Comptable, même appelée directement
    for (const profil of ['secretaire', 'comptable'] as const) {
      await seConnecter(page, profil);
      await page.goto('/scolarite/inscriptions');
      ligne = ligneDe(page, 'TOGBE');
      await expect(ligne).toContainText('En attente');
      await expect(page.locator('form[action*="/valider"]')).toHaveCount(0);

      const jeton = await page.locator('meta[name=csrf-token]').getAttribute('content');
      const refus = await page.request.post(url!, { headers: { 'X-CSRF-TOKEN': jeton! }, maxRedirects: 0 });
      expect(refus.status(), `validation par ${profil}`).toBe(403);
    }

    // Le Comptable ne saisit pas non plus d'inscription
    await expect(page.locator('#btnNouvelleInscription')).toHaveCount(0);
  });
});
