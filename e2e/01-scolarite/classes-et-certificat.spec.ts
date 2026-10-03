import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 1 — Scolarité :
 *   1.4 « Créer / modifier cycles, niveaux et classes » — écran des classes (Administrateur) ;
 *   1.3 « Éditer un certificat de scolarité ».
 */
const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

test.describe('1.4 Classes', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'admin');
  });

  test('la liste présente les classes de l\'année avec leur niveau, leur cycle et leur effectif', async ({ page }) => {
    await page.goto('/scolarite/classes');

    for (const colonne of ['Classe', 'Niveau', 'Cycle', 'Emplacement', 'Élèves inscrits']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    const cpA = ligneDe(page, 'CP A');
    await expect(cpA).toContainText('CP');
    await expect(cpA).toContainText('Primaire');
    // Les deux élèves du jeu de données sont inscrits en CP A
    expect(Number(await cpA.locator('.effectif').innerText())).toBeGreaterThanOrEqual(2);
  });

  test('créer une classe rattachée à un niveau, puis la modifier', async ({ page }) => {
    await page.goto('/scolarite/classes');

    await page.locator('#btnNouvelleClasse').click();
    await page.locator('#classe-libelle').fill('CE1 B');
    await page.locator('#classe-niveau').selectOption({ label: 'CE1' });
    await page.locator('#classe-emplacement').fill('Bâtiment B, salle 2');
    await page.locator('#classe-form button[type=submit]').click();
    await attendreToast(page, 'créée');

    // Le cycle est déduit du niveau
    const classe = ligneDe(page, 'CE1 B');
    await expect(classe).toContainText('Primaire');
    await expect(classe).toContainText('Bâtiment B, salle 2');
    await expect(classe.locator('.effectif')).toHaveText('0');

    await actionDeLigne(page, 'CE1 B', 'Modifier');
    await expect(page.locator('#classe-libelle')).toHaveValue('CE1 B');
    await expect(page.locator('#classe-emplacement')).toHaveValue('Bâtiment B, salle 2');
    await page.locator('#classe-emplacement').fill('Bâtiment B, salle 5');
    await page.locator('#classe-form button[type=submit]').click();
    await attendreToast(page, 'modifiée');
    await expect(ligneDe(page, 'CE1 B')).toContainText('salle 5');
  });

  test('deux classes d\'une même année ne peuvent pas porter le même libellé ; le niveau est obligatoire', async ({ page }) => {
    await page.goto('/scolarite/classes');

    await page.locator('#btnNouvelleClasse').click();
    await page.locator('#classe-libelle').fill('CE1 B');
    await page.locator('#classe-form button[type=submit]').click();

    await expect(page.locator('#classe-libelle-error')).toContainText('déjà ce libellé');
    await expect(page.locator('#classe-niveau_id-error')).not.toBeEmpty();
    await expect(page.locator('#classeModal')).toBeVisible();
  });

  test('la nouvelle classe est proposée à l\'inscription ; le filtre par niveau restreint la liste', async ({ page }) => {
    await page.goto('/scolarite/inscriptions');
    await expect(page.locator('#inscriptionModal [name=classe_id] option', { hasText: 'CE1 B' })).toHaveCount(1);

    await page.goto('/scolarite/classes');
    await page.locator('form[method=GET] select[name=niveau_id]').selectOption({ label: 'CE1' });
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText('CE1 B');
    await expect(page.locator('tbody')).not.toContainText('CP A');
  });

  test('une classe vide se désactive ; une classe qui compte des élèves, non', async ({ page }) => {
    await page.goto('/scolarite/classes');

    // Classe avec des inscrits : refus
    const cpA = ligneDe(page, 'CP A');
    await cpA.locator('.btn-action').click();
    await cpA.locator('form button[type=submit]').click();
    await confirmer(page);
    await expect(page.locator('#toast-container .toast-error')).toContainText('élèves inscrits');
    await expect(ligneDe(page, 'CP A')).toHaveCount(1);

    // Classe vide : désactivée
    const ce1B = ligneDe(page, 'CE1 B');
    await ce1B.locator('.btn-action').click();
    await ce1B.locator('form button[type=submit]').click();
    await confirmer(page);
    await attendreToast(page, 'désactivée');
    await expect(page.locator('tbody')).not.toContainText('CE1 B');
  });

  test('un niveau qui a des classes actives ne peut pas être supprimé', async ({ page }) => {
    await page.goto('/scolarite/niveaux');

    const cp = page.locator('tbody tr').filter({ has: page.locator('td', { hasText: /^\s*CP\s*$/ }) }).first();
    await cp.locator('.btn-action').click();
    await cp.locator('form button[type=submit]').click();
    await confirmer(page);

    await expect(page.locator('#toast-container .toast-error')).toContainText('classes actives');
  });

  test('l\'écran des classes est réservé à l\'Administrateur', async ({ page }) => {
    for (const profil of ['secretaire', 'directeur', 'adjoint'] as const) {
      await seConnecter(page, profil);
      const reponse = await page.request.get('/scolarite/classes', { maxRedirects: 0 });
      expect(reponse.status(), profil).toBe(403);
    }
  });
});

test.describe('1.3 Certificat de scolarité', () => {
  test('le secrétariat édite le certificat d\'un élève dont l\'inscription est validée', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/inscriptions');

    const inscription = ligneDe(page, 'E2E0001');
    await inscription.locator('.btn-action').click();
    const lien = inscription.getByRole('link', { name: 'Certificat de scolarité' });
    await expect(lien).toBeVisible();

    const pdf = await page.request.get((await lien.getAttribute('href'))!);
    expect(pdf.status()).toBe(200);
    expect(pdf.headers()['content-type']).toContain('pdf');
    expect(pdf.headers()['content-disposition']).toContain('certificat_scolarite_E2E0001');
  });

  test('aucun certificat pour une inscription qui n\'est pas validée', async ({ page }) => {
    await seConnecter(page, 'secretaire');

    // Un nouvel élève, inscrit mais pas encore validé par la Direction
    await page.goto('/scolarite/eleves');
    await page.locator('#btn-nouvel-eleve').click();
    const eleve = page.locator('#formEleve');
    await eleve.locator('[name=nom]').fill('ZOGBEMA');
    await eleve.locator('[name=prenom]').fill('Attente');
    await eleve.locator('[name=sexe]').selectOption('1');
    await eleve.locator('[name=date_naissance]').fill('2019-02-02');
    await eleve.locator('button[type=submit]').click();
    await attendreToast(page);

    await page.goto('/scolarite/inscriptions');
    await page.locator('#btnNouvelleInscription').click();
    const modale = page.locator('#inscriptionModal');
    await modale.locator('.select2-selection').click();
    await page.locator('.select2-search__field').fill('ZOGBEMA');
    await page.locator('.select2-results__option', { hasText: 'ZOGBEMA' }).first().click();
    await modale.locator('[name=cycle_id]').selectOption({ label: 'Primaire' });
    await modale.locator('[name=niveau_id]').selectOption({ label: 'CP' });
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    // Le lien n'est pas proposé tant que l'inscription est en attente
    const enAttente = ligneDe(page, 'ZOGBEMA');
    await expect(enAttente).toContainText('En attente');
    await expect(enAttente.getByRole('link', { name: 'Certificat de scolarité' })).toHaveCount(0);
  });
});
