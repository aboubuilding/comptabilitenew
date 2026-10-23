import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité :
 *   2.7 Comptabilité analytique (Contrôleur de gestion, Comptable, Direction en consultation)
 *   2.8 Recouvrement (Chargé de recouvrement, Comptable, Direction)
 *   2.9 Prévisions budgétaires (Comptable, Direction)
 */
const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

test.describe('2.9 Lignes budgétaires', () => {
  test('le Comptable crée une ligne budgétaire (libellé et montant global seulement), puis la modifie', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/previsions');

    for (const colonne of ['Ligne budgétaire', 'Montant global', 'Dépenses', 'Reste']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    await page.locator('#btnNouvellePrevision').click();
    // Ni type (dépense / recette), ni dates
    await expect(page.locator('#previsionModal [name=type], #previsionModal [name=date_debut], #previsionModal [name=date_fin]')).toHaveCount(0);
    await page.locator('#prev-libelle').fill('Denrées cantine T1');
    await page.locator('#prev-montant').fill('900000');
    await page.locator('#prev-centre').selectOption({ label: 'CANT — Cantine' });   // facultatif
    await page.locator('#prev-form button[type=submit]').click();
    await attendreToast(page);

    let ligne = ligneDe(page, 'Denrées cantine T1');
    await expect(ligne).toContainText(/900\s000 F/);
    await expect(ligne).toContainText('CANT — Cantine');

    await actionDeLigne(page, 'Denrées cantine T1', 'Modifier');
    await expect(page.locator('#prev-libelle')).toHaveValue('Denrées cantine T1');
    await expect(page.locator('#prev-centre option:checked')).toHaveText('CANT — Cantine');
    await page.locator('#prev-montant').fill('950000');
    await page.locator('#prev-form button[type=submit]').click();
    await attendreToast(page);

    ligne = ligneDe(page, 'Denrées cantine T1');
    await expect(ligne).toContainText(/950\s000 F/);
  });

  test('une ligne budgétaire sans libellé ni montant est refusée', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/previsions');
    await page.locator('#btnNouvellePrevision').click();
    await page.locator('#prev-form button[type=submit]').click();

    await expect(page.locator('#previsionModal')).toBeVisible();
    await expect(page.locator('#libelle-error')).not.toBeEmpty();
    await expect(page.locator('#montant_previsionnel-error')).not.toBeEmpty();
  });

  test('une dépense « sur ligne budgétaire » exige sa ligne, et ne lui est imputée qu\'une fois décaissée', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/depenses');
    await page.locator('#btnNouvelleDepense').click();
    const formulaire = page.locator('#depense-form');
    await formulaire.locator('[name=libelle]').fill('Riz et huile');
    await formulaire.locator('[name=beneficiaire]').fill('Marché de Hédzranawoé');
    await formulaire.locator('[name=montant]').fill('40000');

    // Hors ligne par défaut ; « sur ligne budgétaire » fait apparaître la ligne, obligatoire
    await expect(formulaire.locator('#imputation-hors-ligne')).toBeChecked();
    await expect(formulaire.locator('#bloc-ligne-budgetaire')).toBeHidden();
    await formulaire.locator('#imputation-ligne').check();
    await expect(formulaire.locator('#bloc-ligne-budgetaire')).toBeVisible();
    await expect(formulaire.locator('[name=ligne_budgetaire_id]')).toHaveAttribute('required', '');
    const ligneBudget = await formulaire.locator('[name=ligne_budgetaire_id] option', { hasText: 'Denrées cantine T1' }).getAttribute('value');
    await formulaire.locator('[name=ligne_budgetaire_id]').selectOption(ligneBudget!);
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Riz et huile')).toContainText('Ligne : Denrées cantine T1');

    // Pas encore décaissée : rien n'est imputé à la ligne
    await page.goto('/finances/previsions');
    let ligne = ligneDe(page, 'Denrées cantine T1');
    await expect(ligne).toContainText(/40\s000 F en attente de décaissement/);
    await expect(ligne).toContainText(/950\s000 F/);   // reste intact

    // Le Caissier la décaisse depuis sa caisse ouverte
    await seConnecter(page, 'caissier');
    await page.goto('/finances/caisses');
    if ((await page.locator('tbody tr').filter({ hasText: 'Ouverte' }).count()) === 0) {
      await page.locator('[data-bs-target="#caisseModal"]').click();
      const modale = page.locator('#caisseModal');
      await modale.locator('[name=libelle]').fill('Caisse lignes budgétaires');
      await modale.locator('[name=solde_initial]').fill('100000');
      await modale.locator('button[type=submit]').click();
      await attendreToast(page);
    }
    await page.goto('/finances/decaissements?onglet=attente');
    await page.locator('#table-attente tbody tr').filter({ hasText: 'Riz et huile' }).locator('.btn-decaisser').click();
    await page.locator('#dec-valider').click();
    await attendreToast(page, 'Décaissement enregistré');

    // Décaissée : imputée à la ligne, avec la caisse du décaissement
    await seConnecter(page, 'comptable');
    await page.goto('/finances/previsions');
    ligne = ligneDe(page, 'Denrées cantine T1');
    await expect(ligne).toContainText(/40\s000 F/);
    await expect(ligne).toContainText(/910\s000 F/);   // reste
    await expect(ligne).not.toContainText('en attente de décaissement');
    await actionDeLigne(page, 'Denrées cantine T1', 'Voir les dépenses');
    const fenetre = page.locator('#depensesLigneModal #depenses-ligne');
    await expect(fenetre).toContainText('Riz et huile');
    await expect(fenetre).toContainText('Décaissée');
    await expect(fenetre).not.toContainText('Pas encore décaissée');
  });

  test('la Direction crée et supprime une ligne budgétaire', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/finances/previsions');

    await page.locator('#btnNouvellePrevision').click();
    await page.locator('#prev-libelle').fill('Sorties pédagogiques');
    await page.locator('#prev-montant').fill('500000');
    await page.locator('#prev-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Sorties pédagogiques')).toContainText(/500\s000 F/);

    const ligne = ligneDe(page, 'Sorties pédagogiques');
    await ligne.locator('.btn-action').click();
    await ligne.locator('form button[type=submit]').click();
    await confirmer(page);
    await attendreToast(page);
    await expect(page.locator('tbody')).not.toContainText('Sorties pédagogiques');
  });

  test('le Contrôleur de gestion consulte les lignes budgétaires sans pouvoir les modifier', async ({ page }) => {
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
    // (la ligne budgétaire « Denrées cantine T1 » de 950 000 lui est rattachée → budget net de −950 000).
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
    const lien = menu.locator('.hnav-drop-item', { hasText: 'Tableau de bord analytique' });
    // Menu long : le groupe ouvre ses écrans dans un second niveau
    const groupe = menu.locator('.hnav-sub').filter({ has: page.locator('.hnav-drop-item', { hasText: 'Tableau de bord analytique' }) });
    if (await groupe.count()) await groupe.hover();
    await lien.click();
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

  test('le recalcul ouvre un dossier pour chaque élève en retard sur une échéance échue', async ({ page }) => {
    // Échéancier de la scolarité du CP : T1 (200 000) échue depuis 30 jours, T2 à venir

    // Recalcul par le Chargé de recouvrement, sur l'année de l'en-tête (plus de sélecteur d'année)
    await seConnecter(page, 'recouvrement');
    await page.goto('/finances/recouvrement');
    await expect(page.locator('#annee-recouvrement')).toBeVisible();
    await expect(page.locator('form[method=GET] select[name=annee_id]')).toHaveCount(0);
    // Confirmation puis bilan dans des fenêtres SweetAlert
    await page.locator('#btnRecalculer').click();
    await expect(page.locator('.swal2-popup')).toContainText('Recalculer le recouvrement');
    await confirmer(page);
    await expect(page.locator('.swal2-popup')).toContainText('Recouvrement recalculé');
    await expect(page.locator('.swal2-popup')).toContainText(/élève\(s\) en retard/);
    await confirmer(page);

    // Cinq colonnes, sans identifiant de dossier ; niveau et classe de l'élève ; échéances réclamées
    await expect(page.locator('#table-dossiers thead th')).toHaveCount(5);
    for (const colonne of ['Élève', 'Échéances réclamées', 'Montant réclamé', 'Statut', 'Actions']) {
      await expect(page.locator('#table-dossiers thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    await expect(page.locator('#onglet-tous')).toHaveClass(/active/);
    await expect(page.locator('#onglet-clotures')).toBeVisible();
    await expect(page.locator('#onglet-relances')).toBeVisible();

    const dossier = ligneDe(page, ELEVE);
    await expect(dossier).toContainText(MATRICULE);
    await expect(dossier).toContainText('CP · CP A');
    await expect(dossier).toContainText('T1');
    await expect(dossier).toContainText(/200\s000 F/);   // seule T1 est échue
    await expect(dossier).toContainText('En cours');
    await expect(dossier).toContainText('Aucune relance');

    // Recalculer une seconde fois ne crée pas de doublon
    await page.locator('#btnRecalculer').click();
    await confirmer(page);
    await expect(page.locator('.swal2-popup')).toContainText('0 dossier(s) ouvert(s)');
    await confirmer(page);
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
    await expect(page.locator('tbody')).toContainText('Pas de dossiers');
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

  test('relance groupée par email depuis la liste, visible dans l\'historique des relances', async ({ page }) => {
    await seConnecter(page, 'recouvrement');
    await page.goto('/finances/recouvrement');

    // Sélection de deux dossiers, puis « Relance groupée »
    await page.locator('#table-dossiers .selection-dossier').nth(0).check();
    await page.locator('#table-dossiers .selection-dossier').nth(1).check();
    await expect(page.locator('#barre-selection')).toBeVisible();
    await expect(page.locator('#nb-selection')).toHaveText('2');
    await page.locator('#btn-relance-groupee').click();
    const fenetre = page.locator('#relanceMessageModal');
    await expect(fenetre.locator('#relance-nombre')).toHaveText('2');
    await expect(fenetre.locator('#relance-message')).toHaveValue(/\{eleve\}/);
    await fenetre.locator('#relance-canal-email').check();
    await fenetre.locator('#relance-valider').click();
    await attendreToast(page, /2 relance\(s\) enregistrée\(s\)/);

    // Historique : l'appel du scénario précédent et les deux emails
    await page.goto('/finances/recouvrement?onglet=relances&canal=email');
    await expect(page.locator('#table-relances tbody tr')).toHaveCount(2);
    await expect(page.locator('#table-relances tbody')).toContainText('Email');

    // Envoi réel : désactivé en développement, messages et statuts conservés
    await expect(page.locator('#envoi-desactive')).toBeVisible();
    await page.locator('#tout-selectionner-relances').check();
    await page.locator('#btn-envoyer-relances').click();
    await confirmer(page);
    await expect(page.locator('.swal2-popup')).toContainText('Envoi non disponible');
    await confirmer(page);
    await expect(page.locator('#table-relances tbody')).not.toContainText('Envoyé');
  });

  test('la planification montre les échéances à venir et relance préventivement par SMS', async ({ page }) => {
    await seConnecter(page, 'recouvrement');
    await page.goto('/finances/recouvrement');
    await page.locator('#lien-planification').click();
    await expect(page.locator('.page-title-main')).toContainText('Planification des relances');

    // T2 de la scolarité du CP tombe dans 90 jours : qui ne sera pas à jour dans 100 jours, au CP, sur la T2
    await expect(page.locator('#table-calendrier')).toHaveCount(0);
    const dans = (jours: number) => new Date(Date.now() + jours * 86400000).toISOString().slice(0, 10);
    await page.locator('#pl-jour').fill(dans(100));
    await page.locator('#pl-nature').selectOption({ label: 'Écolage' });
    await page.locator('#pl-niveau').selectOption({ label: 'CP' });
    await page.locator('#pl-libelle').fill('T2');
    await page.locator('#filtres-planification button[type=submit]').click();
    const liste = page.locator('#table-non-a-jour tbody');
    await expect(liste).toContainText(ELEVE);
    await expect(liste).toContainText('T2');

    // « Des élèves choisis » : Select2 parmi les élèves de la simulation
    await page.locator('#btn-relance-ciblee').click();
    await expect(page.locator('#relanceCibleeModal')).toBeVisible();
    await expect(page.locator('#relance-eleves-choisis option').filter({ hasText: ELEVE })).toHaveCount(1);
    await page.locator('#relanceCibleeModal .btn-annuler').click();

    await liste.locator('tr').filter({ hasText: ELEVE }).locator('.btn-relancer-un').click();
    const fenetre = page.locator('#relanceMessageModal');
    await fenetre.locator('#relance-canal-sms').check();
    await expect(fenetre.locator('#relance-message')).toHaveValue(/EIM/);
    await fenetre.locator('#relance-valider').click();
    await attendreToast(page, /1 relance\(s\) enregistrée\(s\)/);

    await page.goto('/finances/recouvrement?onglet=relances&canal=sms');
    await expect(page.locator('#table-relances tbody')).toContainText(ELEVE);
    // L'élève a déjà un dossier ouvert : la relance s'y rattache ; le message cite l'échéance T2
    await expect(page.locator('#table-relances tbody tr').filter({ hasText: ELEVE }).first()).toContainText('T2');
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
