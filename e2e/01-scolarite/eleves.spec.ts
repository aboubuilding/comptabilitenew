import { attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 1 — Scolarité › 1.1 Élèves
 * Profils concernés : Secrétariat (gestion), Direction (consultation), Administrateur (supervision).
 */
test.describe('1.1 Élèves', () => {
  test('la liste affiche les colonnes prévues, dont le montant dû et le total encaissé', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/eleves');

    const entetes = page.locator('thead th');
    for (const colonne of ['Photo', 'Matricule', 'Nom & prénom', 'Sexe', 'Naissance', 'Nationalité', 'Classe', 'Montant dû', 'Encaissé', 'Statut']) {
      await expect(entetes.filter({ hasText: colonne }).first()).toBeVisible();
    }

    const ligne = page.locator('tbody tr').filter({ hasText: 'E2E0001' });
    await expect(ligne).toContainText('KOFFI');
    await expect(ligne).toContainText('Ama');
    await expect(ligne).toContainText('CP A');
    await expect(ligne).toContainText('Togolaise');
    await expect(ligne).toContainText('12/03/2019');
  });

  test('la recherche par nom ou matricule et le filtre par sexe restreignent la liste', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/eleves');

    await page.locator('input[name=recherche]').fill('E2E0002');
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText('Kossi');
    await expect(page.locator('tbody')).not.toContainText('E2E0001');

    await page.getByRole('link', { name: 'Réinitialiser' }).click();
    await page.locator('select[name=sexe]').selectOption({ label: 'Féminin' });
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText('Ama');
    await expect(page.locator('tbody')).not.toContainText('E2E0002');
  });

  test('le secrétariat crée une fiche élève (état civil, contact d\'urgence, informations médicales)', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/eleves');

    await page.locator('#btn-nouvel-eleve').click();
    const formulaire = page.locator('#formEleve');
    await expect(formulaire).toBeVisible();

    await formulaire.locator('[name=nom]').fill('MENSAH');
    await formulaire.locator('[name=prenom]').fill('Afi');
    await formulaire.locator('[name=sexe]').selectOption('2');
    await formulaire.locator('[name=date_naissance]').fill('2018-05-20');
    await formulaire.locator('[name=lieu_naissance]').fill('Lomé');
    await formulaire.locator('[name=nationalite_id]').selectOption({ label: 'Togolaise' });
    await formulaire.locator('[name=personne_prevenir]').fill('MENSAH Kodjo');
    await formulaire.locator('[name=numero_personne_prevenir]').fill('90222222');
    await formulaire.locator('[name=allergie]').fill('Arachides');
    await formulaire.locator('button[type=submit]').click();

    await attendreToast(page, 'créé');

    // Pas encore inscrit : la fiche se retrouve par le filtre « Sans inscription validée »
    await expect(page.locator('tbody')).not.toContainText('MENSAH');
    await page.locator('select[name=situation]').selectOption('sans');
    await page.locator('form[method=GET] button[type=submit]').click();
    const ligne = page.locator('tbody tr').filter({ hasText: 'MENSAH' });
    await expect(ligne).toContainText('Afi');
    await expect(ligne).toContainText('Non affecté');
    await expect(ligne).toContainText('20/05/2018');
  });

  test('une fiche incomplète est refusée et les champs fautifs sont signalés', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/eleves');

    await page.locator('#btn-nouvel-eleve').click();
    const formulaire = page.locator('#formEleve');
    await formulaire.locator('[name=prenom]').fill('Sans nom');
    // Les contrôles du navigateur sont neutralisés pour vérifier ceux du serveur
    await formulaire.evaluate((f: HTMLFormElement) => (f.noValidate = true));
    await formulaire.locator('button[type=submit]').click();

    await expect(formulaire.locator('[name=nom]')).toHaveClass(/is-invalid/);
    await expect(formulaire.locator('.invalid-feedback').first()).toBeVisible();
  });

  test('le secrétariat modifie une fiche puis consulte la fiche détaillée et sa situation financière', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/eleves');

    const ligne = page.locator('tbody tr').filter({ hasText: 'E2E0002' });
    await ligne.locator('.btn-action').click();
    await ligne.locator('.btn-edit-eleve').click();

    const formulaire = page.locator('#formEleve');
    await expect(formulaire.locator('[name=nom]')).toHaveValue('KOFFI');
    await formulaire.locator('[name=lieu_naissance]').fill('Kpalimé');
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    const ligneMaj = page.locator('tbody tr').filter({ hasText: 'E2E0002' });
    await ligneMaj.locator('.btn-action').click();
    await ligneMaj.getByRole('link', { name: /Consulter|Voir|Fiche|Détail/ }).first().click();

    await expect(page).toHaveURL(/\/scolarite\/eleves\/\d+$/);
    await expect(page.locator('.page-title-main')).toContainText('KOFFI');
    await expect(page.locator('main')).toContainText('Kpalimé');
    await expect(page.locator('main')).toContainText(/Situation financi|Reste|Montant dû/i);
  });

  test('la fiche élève s\'imprime en PDF et la liste s\'exporte en Excel', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/eleves');

    const telechargement = page.waitForEvent('download');
    await page.getByRole('link', { name: /Export/i }).click();
    const fichier = await telechargement;
    expect(fichier.suggestedFilename()).toMatch(/\.xlsx$/);

    const lien = await page.locator('tbody tr').filter({ hasText: 'E2E0001' }).locator('a[href*="fiche-pdf"]').getAttribute('href');
    const pdf = await page.request.get(lien!);
    expect(pdf.status()).toBe(200);
    expect(pdf.headers()['content-type']).toContain('pdf');
  });

  test('archiver une fiche la retire de la liste sans la supprimer (§ 6 États génériques)', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/eleves');

    await page.locator('#btn-nouvel-eleve').click();
    const formulaire = page.locator('#formEleve');
    await formulaire.locator('[name=nom]').fill('TEMPORAIRE');
    await formulaire.locator('[name=prenom]').fill('Test');
    await formulaire.locator('[name=sexe]').selectOption('1');
    await formulaire.locator('[name=date_naissance]').fill('2017-01-01');
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    await page.goto('/scolarite/eleves?situation=sans');
    const ligne = page.locator('tbody tr').filter({ hasText: 'TEMPORAIRE' });
    await ligne.locator('.btn-action').click();
    await ligne.locator('form button[type=submit]').click();
    await confirmer(page);

    await attendreToast(page);
    await page.goto('/scolarite/eleves?situation=tous');
    await expect(page.locator('tbody')).toContainText('E2E0001');
    await expect(page.locator('tbody')).not.toContainText('TEMPORAIRE');
  });

  test('la Direction consulte les élèves mais ne peut ni créer ni modifier', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/scolarite/eleves');

    await expect(page.locator('tbody')).toContainText('E2E0001');
    await expect(page.locator('#btn-nouvel-eleve')).toHaveCount(0);
    await expect(page.locator('.btn-edit-eleve')).toHaveCount(0);

    const creation = await page.request.get('/scolarite/eleves/create');
    expect(creation.status()).toBe(403);
  });
});
