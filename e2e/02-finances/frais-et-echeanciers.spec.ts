import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité › 2.1 Frais scolaires & Échéanciers
 * Profils concernés : Comptable (gestion), Direction (consultation).
 */
test.describe('2.1 Frais scolaires & Échéanciers', () => {
  test('la liste affiche le libellé, le niveau, le montant, le type de paiement et le plan associé', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles?onglet=scolarite');

    // Cinq onglets, sans autre sélecteur d'année que celui de l'en-tête
    for (const onglet of ['Inscription & assurance', 'Scolarité', 'Cantine', 'Transport', 'Tous les frais']) {
      await expect(page.locator('#onglets-frais .nav-link').filter({ hasText: onglet })).toHaveCount(1);
    }
    await expect(page.locator('#filtres-frais [name=annee_id]')).toHaveCount(0);

    // Cinq colonnes au plus
    for (const colonne of ['Frais', 'Niveau', 'Montant', 'Échéancier', 'Actions']) {
      await expect(page.locator('#table-frais thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    await expect(page.locator('#table-frais thead th')).toHaveCount(5);

    const ligne = page.locator('tbody tr').filter({ hasText: 'Scolarité CP' });
    await expect(ligne).toContainText('CP');
    await expect(ligne).toContainText('469 000');
    await expect(ligne).toContainText('Frais de scolarité');
    // La scolarité du CP a son plan d'échéancier (base de test) : elle est échelonnée
    await expect(ligne).toContainText('Échéancier — Scolarité CP');
    await expect(ligne).toContainText('Échelonné');
    // Le montant récolté par frais n'est pas montré au Comptable
    await expect(page.locator('#col-recolte')).toHaveCount(0);

    // « Tous les frais » réunit tous les types ; la cantine a son onglet
    await page.locator('#onglet-tous').click();
    await expect(page.locator('#table-frais thead th')).toHaveCount(5);
    await expect(page.locator('#table-frais tbody')).toContainText('Cantine CP');
    await page.locator('#onglet-cantine').click();
    await expect(page.locator('#table-frais tbody')).toContainText('Cantine CP');
    await expect(page.locator('#table-frais tbody')).not.toContainText('Scolarité CP');
  });

  test('le Comptable définit un frais, puis le modifie', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles?onglet=scolarite');

    await page.locator('#btnNouveauFrais').click();
    await page.locator('#frais-libelle').fill('Scolarité CE1');
    await page.locator('#frais-niveau').selectOption({ label: 'CE1' });
    await page.locator('#frais-montant').fill('480000');
    const scolarite = await page.locator('#frais-type-paiement option', { hasText: /scola/i }).first().getAttribute('value');
    await page.locator('#frais-type-paiement').selectOption(scolarite!);
    await page.locator('#frais-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = page.locator('tbody tr').filter({ hasText: 'Scolarité CE1' });
    await expect(ligne).toContainText('480 000');
    await expect(ligne).toContainText('Comptant');

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

  test('un frais échelonné se définit avec ses échéances ; seuls trois types de frais se créent ici', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles?onglet=scolarite');

    await page.locator('#btnNouveauFrais').click();
    // Inscription, assurance et scolarité seulement : la cantine et le transport ont leurs modules
    await expect(page.locator('#frais-type-paiement option')).toHaveCount(3);
    await expect(page.locator('#frais-type-paiement option', { hasText: /cantine|transport/i })).toHaveCount(0);

    await page.locator('#frais-libelle').fill('Scolarité CE2');
    await page.locator('#frais-niveau').selectOption({ label: 'CE1' });
    await page.locator('#frais-montant').fill('300000');
    const scolarite = await page.locator('#frais-type-paiement option', { hasText: /scola/i }).first().getAttribute('value');
    await page.locator('#frais-type-paiement').selectOption(scolarite!);
    const echelonne = await page.locator('#frais-modalite-paiement option', { hasText: /chelonn/i }).first().getAttribute('value');
    await page.locator('#frais-modalite-paiement').selectOption(echelonne!);

    // Trois échéances réparties, puis ajustées
    await page.locator('#echeances-nombre').fill('3');
    await page.locator('#echeances-repartir').click();
    const lignes = page.locator('#echeances-table tbody tr');
    await expect(lignes).toHaveCount(3);
    for (const [i, date] of ['2026-10-15', '2026-12-15', '2027-02-15'].entries()) {
      await lignes.nth(i).locator('.ech-date').fill(date);
    }
    await lignes.nth(0).locator('.ech-libelle').fill('Rentrée');

    // Une somme différente du montant est refusée
    await lignes.nth(0).locator('.ech-montant').fill('150000');
    await page.locator('#frais-form button[type=submit]').click();
    await expect(page.locator('#frais-echeances-error')).toContainText(/somme des échéances/i);
    await expect(page.locator('#fraisModal')).toBeVisible();

    await lignes.nth(1).locator('.ech-montant').fill('75000');
    await lignes.nth(2).locator('.ech-montant').fill('75000');
    await expect(page.locator('#echeances-total')).toHaveText(/300\s000/);
    await page.locator('#frais-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = page.locator('tbody tr').filter({ hasText: 'Scolarité CE2' });
    await expect(ligne).toContainText('Échéancier — Scolarité CE2');

    // Le plan créé figure parmi les plans de l'année : frais lié, montants et dates butoirs, en cinq colonnes
    await page.goto('/finances/plan-echeanciers');
    const plan = page.locator('.table-plans > tbody > tr').filter({ hasText: 'Échéancier — Scolarité CE2' });
    await expect(page.locator('.table-plans > thead th')).toHaveCount(5);
    await expect(plan.locator('.badge-lignes-count')).toHaveText('3');
    await expect(plan.locator('.frais-lies')).toContainText('Scolarité CE2');
    await expect(plan.locator('.echeances')).toContainText('Rentrée');
    await expect(plan.locator('.echeances')).toContainText(/150\s000 F/);
    await expect(plan.locator('.echeances')).toContainText('15/10/2026');
    await expect(plan).toContainText(/300\s000 F/);
    // Le tableau tient dans la page, sans défilement horizontal
    const deborde = await page.locator('.table-plans').evaluate((t) => t.parentElement!.scrollWidth > t.parentElement!.clientWidth + 1);
    expect(deborde).toBe(false);

    // Un plan ne se crée plus ici : il naît avec son frais. Il reste modifiable.
    await expect(page.locator('#btnNouveauPlan')).toHaveCount(0);
    await plan.locator('.btn-action').click();
    await plan.locator('.btn-edit-plan').click();
    await expect(page.locator('#plan-nom')).toHaveValue('Échéancier — Scolarité CE2');
    await expect(page.locator('#plan-lignes tbody tr')).toHaveCount(3);

    // L'ancien écran des tranches renvoie vers les plans
    await page.goto('/finances/tranches');
    await expect(page).toHaveURL(/\/finances\/plan-echeanciers/);
  });

  test('la fiche d\'un frais liste ses souscriptions ; la liste s\'exporte', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/frais-ecoles?onglet=cantine');

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
    await expect(page.locator('#table-frais tbody')).not.toContainText('Frais à retirer');
  });

  test('la Direction consulte les frais sans pouvoir les modifier', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/finances/frais-ecoles?onglet=scolarite');

    await expect(page.locator('tbody')).toContainText('Scolarité CP');
    // Ni création, ni synchronisation des catalogues ; elle voit le montant récolté par frais
    await expect(page.locator('#btn-synchroniser')).toHaveCount(0);
    await expect(page.locator('#col-recolte')).toContainText('récolté');
    await expect(page.locator('#btnNouveauFrais')).toHaveCount(0);
    await expect(page.locator('.btn-edit-frais')).toHaveCount(0);

    // Elle consulte les échéances d'un plan sans pouvoir les modifier
    await page.goto('/finances/plan-echeanciers');
    await expect(page.locator('#btnNouveauPlan')).toHaveCount(0);
    const plan = page.locator('.table-plans > tbody > tr').filter({ hasText: 'Échéancier — Scolarité CE2' });
    await plan.locator('.btn-action').click();
    await plan.locator('.btn-edit-plan').click();
    await expect(page.locator('#plan-lignes tbody tr')).toHaveCount(3);
    await expect(page.locator('#plan-enregistrer')).toBeHidden();
    await expect(page.locator('#plan-ajouter-ligne')).toBeHidden();
  });
});
