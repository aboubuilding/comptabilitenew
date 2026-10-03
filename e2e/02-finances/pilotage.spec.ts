import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité :
 *   2.7 Comptabilité analytique (Contrôleur de gestion, Comptable, Direction en consultation)
 *   2.8 Recouvrement (Chargé de recouvrement, Comptable, Direction)
 *   2.9 Prévisions budgétaires (Comptable, Direction)
 */
const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

test.describe('2.9 Prévisions budgétaires', () => {
  test('le Comptable saisit une prévision de dépense rattachée à un centre, puis la modifie', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/previsions');

    for (const colonne of ['Libellé', 'Type', 'Période', 'Prévisionnel', 'Réalisé', 'Écart']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    await page.locator('#btnNouvellePrevision').click();
    await page.locator('#prev-libelle').fill('Denrées cantine T1');
    await page.locator('#prev-type').selectOption({ label: 'Dépense' });
    await page.locator('#prev-montant').fill('900000');
    await page.locator('#prev-centre').selectOption({ label: 'CANT — Cantine' });
    await page.locator('#prev-debut').fill('2026-09-15');
    await page.locator('#prev-fin').fill('2026-12-20');
    await page.locator('#prev-form button[type=submit]').click();
    await attendreToast(page);

    let ligne = ligneDe(page, 'Denrées cantine T1');
    await expect(ligne).toContainText('Dépense');
    await expect(ligne).toContainText('CANT');
    await expect(ligne).toContainText('15/09/2026 → 20/12/2026');
    await expect(ligne).toContainText('900 000,00');

    await actionDeLigne(page, 'Denrées cantine T1', 'Modifier');
    await expect(page.locator('#prev-libelle')).toHaveValue('Denrées cantine T1');
    await expect(page.locator('#prev-debut')).toHaveValue('2026-09-15');
    await page.locator('#prev-montant').fill('950000');
    await page.locator('#prev-form button[type=submit]').click();
    await attendreToast(page);

    ligne = ligneDe(page, 'Denrées cantine T1');
    await expect(ligne).toContainText('950 000,00');
  });

  test('une prévision sans libellé ni montant est refusée', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/previsions');
    await page.locator('#btnNouvellePrevision').click();
    await page.locator('#prev-form button[type=submit]').click();

    await expect(page.locator('#previsionModal')).toBeVisible();
    await expect(page.locator('#libelle-error')).not.toBeEmpty();
    await expect(page.locator('#montant_previsionnel-error')).not.toBeEmpty();
  });

  test('la Direction saisit une prévision de recette et supprime une prévision', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/finances/previsions');

    await page.locator('#btnNouvellePrevision').click();
    await page.locator('#prev-libelle').fill('Écolages T1');
    await page.locator('#prev-type').selectOption({ label: 'Recette' });
    await page.locator('#prev-montant').fill('5000000');
    await page.locator('#prev-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Écolages T1')).toContainText('Recette');

    // Filtre par type
    await page.locator('form[method=GET] select[name=type]').selectOption({ label: 'Recette' });
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText('Écolages T1');
    await expect(page.locator('tbody')).not.toContainText('Denrées cantine T1');

    const ligne = ligneDe(page, 'Écolages T1');
    await ligne.locator('.btn-action').click();
    await ligne.locator('form button[type=submit]').click();
    await confirmer(page);
    await attendreToast(page);
    await expect(page.locator('tbody')).not.toContainText('Écolages T1');
  });

  test('le Contrôleur de gestion consulte les prévisions sans pouvoir les modifier', async ({ page }) => {
    await seConnecter(page, 'controleur');
    await page.goto('/finances/previsions');

    await expect(page.locator('tbody')).toContainText('Denrées cantine T1');
    await expect(page.locator('#btnNouvellePrevision')).toHaveCount(0);
    await expect(page.locator('.btn-edit-prev')).toHaveCount(0);
  });
});

test.describe('2.7 Comptabilité analytique', () => {
  test('le Contrôleur de gestion paramètre un centre analytique et le modifie', async ({ page }) => {
    await seConnecter(page, 'controleur');
    await page.goto('/finances/analytique/centres');
    await expect(ligneDe(page, 'CANT')).toContainText('Cantine');

    await page.locator('#btnNouveauCentre').click();
    await page.locator('#centre-code').fill('TRANSP');
    await page.locator('#centre-libelle').fill('Transport scolaire');
    await page.locator('#centre-type').selectOption({ index: 0 });
    await page.locator('#centre-description').fill('Bus, carburant, chauffeurs');
    await page.locator('#centre-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'TRANSP')).toContainText('Transport scolaire');

    await actionDeLigne(page, 'TRANSP', 'Modifier');
    await expect(page.locator('#centre-code')).toHaveValue('TRANSP');
    await page.locator('#centre-libelle').fill('Transport');
    await page.locator('#centre-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'TRANSP')).toContainText('Transport');
  });

  test('deux centres ne peuvent pas porter le même code', async ({ page }) => {
    await seConnecter(page, 'controleur');
    await page.goto('/finances/analytique/centres');

    await page.locator('#btnNouveauCentre').click();
    await page.locator('#centre-code').fill('CANT');
    await page.locator('#centre-libelle').fill('Doublon');
    await page.locator('#centre-form button[type=submit]').click();

    await expect(page.locator('#centreModal')).toBeVisible();
    await expect(page.locator('#code-error')).not.toBeEmpty();
    await page.reload();
    await expect(page.locator('tbody')).not.toContainText('Doublon');
  });

  test('le Contrôleur de gestion définit une règle de ventilation automatique par nature', async ({ page }) => {
    await seConnecter(page, 'controleur');
    await page.goto('/finances/regles');

    await page.locator('#btnNouvelleRegle').click();
    await page.locator('#regle-nature').fill('cantine');
    await page.locator('#regle-sens').selectOption({ label: 'Recette' });
    await page.locator('#regle-centre').selectOption({ label: 'CANT — Cantine' });
    await page.locator('#regle-pourcentage').fill('100');
    await page.locator('#regle-form button[type=submit]').click();
    await attendreToast(page);

    const ligne = ligneDe(page, 'cantine');
    await expect(ligne).toContainText('Recette');
    await expect(ligne).toContainText('CANT');
    await expect(ligne).toContainText('100');
  });

  test('le résultat par centre affiche recettes, dépenses, résultat et écart au budget, et s\'exporte', async ({ page }) => {
    await seConnecter(page, 'controleur');
    await page.goto('/finances/analytique/rapports');

    for (const colonne of ['Centre analytique', 'Recettes', 'Dépenses', 'Résultat', 'Budget', 'Écart budget']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    // Le centre Cantine figure au rapport même sans écriture, avec son budget
    // (une prévision de dépense de 950 000 → budget net de −950 000).
    const cantine = ligneDe(page, 'Cantine');
    await expect(cantine).toBeVisible();
    await expect(cantine).toContainText('950 000');

    for (const motif of ['/rapports/export', 'export-pdf']) {
      const lien = await page.locator(`a[href*="${motif}"]`).first().getAttribute('href');
      const reponse = await page.request.get(lien!);
      expect(reponse.status(), motif).toBe(200);
    }

  });

  test('le tableau de bord analytique s\'affiche pour la Direction, en consultation seule', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/tableau');
    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Finances' }).first();
    await menu.hover();
    await menu.locator('.hnav-drop-item', { hasText: 'Tableau de bord analytique' }).click();
    await expect(page.locator('main.content-area')).toBeVisible();
    await expect(page.locator('main')).toContainText(/Recettes|Dépenses|Résultat/);

    // La Direction consulte les centres mais ne les paramètre pas
    await page.goto('/finances/analytique/centres');
    await expect(page.locator('tbody')).toContainText('CANT');
    await expect(page.locator('#btnNouveauCentre')).toHaveCount(0);
    await expect(page.locator('.btn-edit-centre')).toHaveCount(0);
  });
});

test.describe('2.8 Recouvrement', () => {
  test.describe.configure({ mode: 'serial' });

  const ELEVE = 'KOFFI Kossi';
  const MATRICULE = 'E2E0002';

  test('un impayé ancien ouvre automatiquement un dossier au recalcul', async ({ page }) => {
    // Engagement : le Comptable souscrit l'écolage (469 000) pour un élève inscrit depuis 45 jours
    await seConnecter(page, 'comptable');
    await page.goto('/finances/souscriptions');
    await page.locator('[data-bs-target="#souscModal"]').click();
    const modale = page.locator('#souscModal');
    const frais = await modale.locator('[name=frais_ecole_id] option', { hasText: 'Scolarité CP' }).getAttribute('value');
    await modale.locator('[name=frais_ecole_id]').selectOption(frais!);
    await modale.locator('.select2-selection').click();
    await page.locator('.select2-search__field').fill('Kossi');
    await page.locator('.select2-results__option', { hasText: MATRICULE }).click();
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    // Recalcul des situations par le Chargé de recouvrement
    await seConnecter(page, 'recouvrement');
    await page.goto('/finances/recouvrement');
    page.once('dialog', (dialogue) => dialogue.accept());
    await page.locator('#btnRecalculer').click();
    await attendreToast(page);

    for (const colonne of ['Élève', 'Nature', 'Initial', 'Recouvré', 'Restant', 'Statut', 'Dernière relance']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    const dossier = ligneDe(page, ELEVE);
    await expect(dossier).toContainText(MATRICULE);
    await expect(dossier).toContainText('CP A');
    await expect(dossier).toContainText('469 000,00');
    await expect(dossier).toContainText('En cours');
    await expect(dossier).toContainText('Aucune');

    // Recalculer une seconde fois ne crée pas de doublon
    page.once('dialog', (dialogue) => dialogue.accept());
    await page.locator('#btnRecalculer').click();
    await attendreToast(page);
    await expect(ligneDe(page, ELEVE)).toHaveCount(1);
  });

  test('la recherche retrouve un dossier par le nom ou le matricule de l\'élève', async ({ page }) => {
    await seConnecter(page, 'recouvrement');
    await page.goto('/finances/recouvrement');

    await page.locator('input[name=search]').fill(MATRICULE);
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(ligneDe(page, ELEVE)).toHaveCount(1);

    await page.locator('input[name=search]').fill('inconnu-xyz');
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText('Aucun dossier');
  });

  test('le Chargé de recouvrement enregistre une relance avec son canal, sa date et son résultat', async ({ page }) => {
    await seConnecter(page, 'recouvrement');
    await page.goto('/finances/recouvrement');

    const dossier = ligneDe(page, ELEVE);
    await dossier.locator('.btn-action').click();
    await dossier.getByRole('link', { name: 'Voir le dossier' }).click();
    await expect(page.locator('.page-title-main')).toContainText('Dossier de recouvrement');
    await expect(page.locator('main')).toContainText(ELEVE);
    await expect(page.locator('main')).toContainText('Aucune relance enregistrée');

    await page.locator('[data-bs-target="#relanceModal"]').click();
    const modale = page.locator('#relanceModal');
    await modale.locator('[name=type_relance]').selectOption({ label: 'Appel téléphonique' });
    await modale.locator('[name=date_relance]').fill('2026-10-01');
    await modale.locator('[name=resultat]').selectOption({ label: 'Promesse de paiement' });
    await modale.locator('[name=date_prochaine_relance]').fill('2026-10-15');
    await modale.locator('[name=contenu]').fill('Le père s\'engage à régler avant le 15 octobre.');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    const historique = page.locator('#relance');
    await expect(historique).toContainText('1 action(s)');
    await expect(historique).toContainText('Appel téléphonique');
    await expect(historique).toContainText('01/10/2026');
    await expect(historique).toContainText('Promesse de paiement');
    await expect(historique).toContainText('régler avant le 15 octobre');
    await expect(historique).toContainText('15/10/2026');

    // La liste rappelle la date et le résultat de la dernière relance
    await page.goto('/finances/recouvrement');
    await expect(ligneDe(page, ELEVE)).toContainText('01/10/2026');
    await expect(ligneDe(page, ELEVE)).toContainText('Promesse de paiement');
  });

  test('la Direction consulte le dossier et le fait passer en échéancier', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/finances/recouvrement');

    const dossier = ligneDe(page, ELEVE);
    await dossier.locator('.btn-action').click();
    await dossier.getByRole('link', { name: 'Voir le dossier' }).click();

    await page.locator('[data-bs-target="#statutModal"]').click();
    const modale = page.locator('#statutModal');
    await modale.locator('[name=statut]').selectOption({ label: 'Échéancier accepté' });
    await modale.locator('[name=motif]').fill('Règlement en trois fois accepté');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);
    await expect(page.locator('main')).toContainText('Échéancier accepté');

    await page.goto('/finances/recouvrement');
    await expect(ligneDe(page, ELEVE)).toContainText('Échéancier accepté');
  });

  test('la liste des impayés s\'exporte', async ({ page }) => {
    await seConnecter(page, 'recouvrement');
    await page.goto('/finances/recouvrement');
    const reponse = await page.request.get(await page.locator('a[href*="/recouvrement/export"]').getAttribute('href').then((h) => h!));
    expect(reponse.status()).toBe(200);
  });

  test('le Caissier n\'a pas accès au recouvrement', async ({ page }) => {
    await seConnecter(page, 'caissier');
    const reponse = await page.request.get('/finances/recouvrement', { maxRedirects: 0 });
    expect(reponse.status()).toBe(403);
  });
});
