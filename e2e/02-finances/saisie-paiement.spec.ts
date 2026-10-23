import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, encaisserPaiement, expect, ouvrirSaisiePaiement, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité › 2.2 Paiements : l'écran « Nouveau paiement ».
 *
 * 1. on recherche l'élève dans une liste (nom, prénom ou matricule) ;
 * 2. sa situation s'affiche frais par frais (dû, déjà payé, reste), avec ses
 *    paiements passés ;
 * 3. on saisit le nouveau paiement — total ou partiel — et des articles, en
 *    espèces ou par chèque (le chèque demande ses références) ;
 * 4. l'enregistrement ramène à la liste des paiements (5 colonnes), où
 *    l'Administrateur et la Direction voient en plus les totaux encaissés.
 *
 * L'élève ADJO Sena (E2E0003) est enregistrée comme dans la base historique :
 * son dû est porté par l'inscription, et elle a déjà versé une tranche.
 */
test.describe.configure({ mode: 'serial' });

const MATRICULE = 'E2E0003';
const ligne = (page: Page, table: string, texte: string | RegExp) => page.locator(`${table} tbody tr`).filter({ hasText: texte });

test.describe('2.2 Nouveau paiement', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'comptable');
  });

  test('« Nouveau paiement » ouvre une recherche d\'élève : nom, prénom ou matricule', async ({ page }) => {
    await page.goto('/finances/paiements');
    await page.getByRole('link', { name: /Nouveau paiement/ }).first().click();

    await expect(page.locator('#invite-recherche')).toBeVisible();
    await expect(page.locator('#paiement-form')).toHaveCount(0);

    const champ = page.locator('.select2-container--open .select2-search__field');
    await expect(champ).toBeVisible();

    // Une seule lettre : la recherche attend
    await champ.fill('a');
    await expect(page.locator('.select2-results')).toContainText('au moins 2 caractères');

    // Par le prénom
    await champ.fill('sena');
    const resultat = page.locator('.select2-results__option', { hasText: 'ADJO Sena' });
    await expect(resultat).toContainText(MATRICULE);
    await expect(resultat).toContainText('CE1 A');

    // Par le matricule
    await champ.fill(MATRICULE);
    await expect(page.locator('.select2-results__option', { hasText: 'ADJO Sena' })).toHaveCount(1);

    // Un élève inconnu
    await champ.fill('zzzzzz');
    await expect(page.locator('.select2-results')).toContainText('Aucun élève');
  });

  test('l\'élève retrouvé, sa situation s\'affiche frais par frais avec ses paiements passés', async ({ page }) => {
    await ouvrirSaisiePaiement(page, 'adjo', MATRICULE);

    await expect(page.locator('#eleve-bandeau')).toContainText('ADJO Sena');
    await expect(page.locator('#eleve-bandeau')).toContainText('CE1 A');

    // Situation : scolarité 480 000 − 5 % = 456 000, dont 200 000 déjà payés
    for (const colonne of ['Frais', 'Dû', 'Déjà payé', 'En attente', 'Reste']) {
      await expect(page.locator('#situation-frais thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    const scolarite = ligne(page, '#situation-frais', 'Frais de scolarité');
    await expect(scolarite).toContainText('456 000');
    await expect(scolarite).toContainText('200 000');
    await expect(scolarite).toContainText('256 000');

    await expect(ligne(page, '#situation-frais', 'Frais d\'inscription')).toContainText('50 000');
    await expect(ligne(page, '#situation-frais', 'Frais d\'assurance')).toContainText('Soldé');
    await expect(ligne(page, '#situation-frais', 'Cantine')).toContainText('108 000');

    // Chiffres clés : dû 624 000, encaissé 210 000, reste 414 000
    await expect(page.locator('#total-du')).toHaveText('624 000');
    await expect(page.locator('#total-paye')).toHaveText('210 000');
    await expect(page.locator('#total-reste')).toHaveText('414 000');

    // Paiements passés
    const tranche = ligne(page, '#historique-paiements', 'Scolarité 1re tranche');
    await expect(tranche).toContainText('PAI-HIST-0001');
    await expect(tranche).toContainText('20/09/2026');
    await expect(tranche).toContainText('200 000');
    await expect(tranche).toContainText('Espèces');
    await expect(tranche).toContainText('Encaissé');
    await expect(page.locator('#historique-paiements tbody tr')).toHaveCount(2);
  });

  test('un frais soldé n\'est plus payable ; le montant saisi est plafonné au reste dû', async ({ page }) => {
    await ouvrirSaisiePaiement(page, MATRICULE);

    // Assurance soldée : pas de saisie possible
    const assurance = page.locator('.engagement-row').filter({ hasText: 'Frais d\'assurance' });
    await expect(assurance).toHaveClass(/solde/);
    await expect(assurance.locator('.engagement-saisie')).toHaveCount(0);

    // Scolarité : reste 256 000, un montant supérieur est ramené au reste
    const scolarite = page.locator('.engagement-row.exists').filter({ hasText: 'Frais de scolarité' });
    await scolarite.locator('.engagement-saisie').fill('999999');
    await expect(scolarite.locator('.engagement-saisie')).toHaveValue('256000');
    await expect(page.locator('#toast-container .toast-error').last()).toContainText('reste dû');
    await expect(page.locator('#total-montant')).toHaveText(/256\s000,00/);

    // Rien n'est enregistré tant qu'on n'a pas validé
    await expect(page.locator('#btn-submit')).toBeEnabled();
  });

  test('saisir un paiement partiel de scolarité, la cantine en totalité et un article du catalogue', async ({ page }) => {
    await ouvrirSaisiePaiement(page, MATRICULE);

    // Tranche de scolarité : 100 000 sur les 256 000 restants
    await page.locator('.engagement-row.exists').filter({ hasText: 'Frais de scolarité' }).locator('.engagement-saisie').fill('100000');

    // Cantine : un clic sur la ligne la solde (108 000)
    const cantine = page.locator('.engagement-row.exists').filter({ hasText: 'Cantine' });
    await cantine.click();
    await expect(cantine.locator('.engagement-saisie')).toHaveValue('108000');

    // Catalogue : articles vendus comme frais, pour le niveau de l'élève
    await page.locator('#search-panier').click();
    const catalogue = page.locator('#search-results .search-result-item');
    await expect(catalogue.filter({ hasText: 'TENUE_DE_SPORT' })).toContainText(/7\s500,00/);
    await expect(catalogue.filter({ hasText: 'LIVRES_CE1' })).toBeVisible();
    await expect(catalogue.filter({ hasText: 'Scolarité CP' })).toHaveCount(0); // un frais à souscrire n'est pas un article
    await catalogue.filter({ hasText: 'TENUE_DE_SPORT' }).click();
    await page.locator('.panier-qte').fill('2');

    // 100 000 + 108 000 + 2 × 7 500 = 223 000
    await expect(page.locator('#panier-body tr')).toHaveCount(3);
    await expect(page.locator('#total-lignes')).toHaveText('3');
    await expect(page.locator('#total-montant')).toHaveText(/223\s000,00/);

    await page.locator('#payeur').fill('ADJO Kokou');

    // Modes disponibles pour le moment : espèces et chèque
    await expect(page.locator('#mode_paiement option')).toHaveText(['Espèces', 'Chèque']);
    await expect(page.locator('#zone-cheque')).toBeHidden();

    // Chèque : la zone de saisie de ses références apparaît, numéro et banque sont exigés
    await page.locator('#mode_paiement').selectOption({ label: 'Chèque' });
    await expect(page.locator('#zone-cheque')).toBeVisible();
    await page.locator('#btn-submit').click();
    await expect(page.locator('#toast-container .toast-error').last()).toContainText('numéro et la banque');
    await expect(page).toHaveURL(/\/finances\/paiements\/\d+$/);

    await page.locator('#cheque-numero').fill('CHQ-E2E-0001');
    await page.locator('#cheque-banque').selectOption({ label: 'ECOBANK' });
    await page.locator('#btn-submit').click();

    // Après l'enregistrement : retour à la liste des paiements, avec le message
    await expect(page).toHaveURL(/\/finances\/paiements$/);
    await attendreToast(page, 'Paiement enregistré');

    // La liste tient en 5 colonnes ; les données y sont regroupées
    await expect(page.locator('table thead th')).toHaveText(['Paiement', 'Élève / Payeur', 'Détail', 'Montant', 'Actions']);
    const enregistre = page.locator('tbody tr').filter({ hasText: 'ADJO Kokou' }).first();
    await expect(enregistre.locator('td')).toHaveCount(5);
    await expect(enregistre.locator('.reference')).not.toBeEmpty();
    await expect(enregistre).toContainText('ADJO Sena');
    await expect(enregistre).toContainText(MATRICULE);
    await expect(enregistre).toContainText('Chèque n° CHQ-E2E-0001');
    await expect(enregistre).toContainText('ECOBANK');
    await expect(enregistre).toContainText(/223\s000,00/);
    await expect(enregistre).toContainText('En attente d\'encaissement');

    // Le chèque est entré au portefeuille pour le montant total du paiement
    await page.goto('/finances/cheques');
    const cheque = page.locator('tbody tr').filter({ hasText: 'CHQ-E2E-0001' }).first();
    await expect(cheque).toContainText(/223\s000/);

    // Le contenu du paiement qui a abouti au chèque se consulte depuis le chèque
    await cheque.locator('.btn-action').click();
    await cheque.locator('.btn-voir-paiement').click();
    const fenetre = page.locator('#paiementModal');
    await expect(fenetre.locator('.contenu-paiement')).toBeVisible();
    await expect(fenetre).toContainText('ADJO Sena');
    await expect(fenetre).toContainText('TENUE_DE_SPORT');
    await expect(fenetre).toContainText(/223\s000 F/);
    await fenetre.locator('.btn-annuler').click();

    // De retour sur l'élève : le paiement est en attente d'encaissement
    await ouvrirSaisiePaiement(page, MATRICULE);
    await expect(page.locator('#eleve-bandeau')).toContainText('ADJO Sena');
    await expect(page.locator('#total-attente')).toHaveText('208 000');   // scolarité + cantine (l'article n'est pas un engagement)
    await expect(page.locator('#total-reste')).toHaveText('414 000');     // inchangé tant que le caissier n'a pas encaissé

    const scolarite = ligne(page, '#situation-frais', 'Frais de scolarité');
    await expect(scolarite).toContainText('100 000');

    // Historique : les trois nouvelles lignes, en attente
    await expect(page.locator('#historique-paiements tbody tr')).toHaveCount(5);
    const tenue = ligne(page, '#historique-paiements', 'TENUE_DE_SPORT');
    await expect(tenue).toContainText('15 000');
    await expect(tenue).toContainText('Chèque');
    await expect(tenue).toContainText('En attente d\'encaissement');

    // La cantine, entièrement saisie, n'est plus proposée ; la scolarité l'est pour le solde restant
    await expect(page.locator('.engagement-row.attente').filter({ hasText: 'Cantine' })).toBeVisible();
    await expect(page.locator('.engagement-row.exists').filter({ hasText: 'Frais de scolarité' })).toContainText(/156\s000,00/);
  });

  test('le paiement figure dans la liste, puis l\'encaissement met à jour la situation de l\'élève', async ({ page }) => {
    await page.goto('/finances/paiements');
    const paiement = page.locator('tbody tr').filter({ hasText: 'ADJO Kokou' }).first();
    await expect(paiement).toContainText(/223\s000,00/);
    await expect(paiement).toContainText('En attente d\'encaissement');

    // Le Caissier encaisse les trois lignes
    await seConnecter(page, 'caissier');
    await page.goto('/finances/caisses');
    if ((await page.locator('tbody tr').filter({ hasText: 'Ouverte' }).count()) === 0) {
      await page.locator('[data-bs-target="#caisseModal"]').click();
      const modale = page.locator('#caisseModal');
      await modale.locator('[name=libelle]').fill('Caisse saisie');
      await modale.locator('[name=solde_initial]').fill('0');
      await modale.locator('button[type=submit]').click();
      await attendreToast(page);
    }
    // Un seul encaissement pour le paiement et ses trois lignes (chèque : montant exact)
    await encaisserPaiement(page, 'ADJO Kokou');

    // Situation mise à jour : encaissé 210 000 + 208 000, reste 414 000 − 208 000
    await seConnecter(page, 'comptable');
    await ouvrirSaisiePaiement(page, MATRICULE);
    await expect(page.locator('#total-paye')).toHaveText('418 000');
    await expect(page.locator('#total-attente')).toHaveText('0');
    await expect(page.locator('#total-reste')).toHaveText('206 000');
    await expect(ligne(page, '#situation-frais', 'Cantine')).toContainText('Soldé');
    await expect(ligne(page, '#historique-paiements', 'TENUE_DE_SPORT')).toContainText('Encaissé');
  });

  test('la liste tient dans l\'écran ; l\'action « Détail » résume le paiement', async ({ page }) => {
    await page.goto('/finances/paiements');

    // Pas de défilement horizontal : la colonne Actions est visible d'emblée
    const zone = page.locator('.table-responsive').first();
    const { scrollWidth, clientWidth } = await zone.evaluate((el) => ({ scrollWidth: el.scrollWidth, clientWidth: el.clientWidth }));
    expect(scrollWidth, 'largeur du tableau').toBeLessThanOrEqual(clientWidth + 1);
    const ligne = page.locator('tbody tr').filter({ hasText: 'ADJO Kokou' }).first();
    await expect(ligne.locator('.btn-action')).toBeInViewport();

    await actionDeLigne(page, 'ADJO Kokou', 'Détail');
    await expect(page).toHaveURL(/\/finances\/paiements\/\d+\/detail$/);
    await expect(page.locator('.page-title-main')).toContainText('Détail du paiement');

    const detail = page.locator('#detail-paiement');
    await expect(detail.locator('.reference').first()).not.toBeEmpty();
    await expect(page.locator('#detail-statut')).toContainText('Encaissé');
    await expect(page.locator('#detail-montant')).toContainText(/223\s000,00/);
    await expect(page.locator('#detail-eleve')).toContainText('ADJO Sena');
    await expect(page.locator('#detail-eleve')).toContainText(MATRICULE);
    await expect(page.locator('#detail-eleve')).toContainText('CE1 A');
    await expect(page.locator('#detail-eleve')).toContainText('ADJO Kokou');
    await expect(page.locator('#detail-reglement')).toContainText('Chèque');
    await expect(page.locator('#detail-reglement')).toContainText('CHQ-E2E-0001');
    await expect(page.locator('#detail-reglement')).toContainText('ECOBANK');

    // Les trois lignes, encaissées par le caissier
    await expect(page.locator('#detail-lignes tbody tr')).toHaveCount(3);
    const tenue = page.locator('#detail-lignes tbody tr').filter({ hasText: 'TENUE_DE_SPORT' });
    await expect(tenue).toContainText(/15\s000,00/);
    await expect(tenue).toContainText('2 ×');
    await expect(tenue).toContainText('Encaissé');
    await expect(page.locator('#detail-historique li')).toHaveCount(4);   // saisie + 3 encaissements
    await expect(page.locator('#detail-encaisse')).toHaveText('223 000');

    // Lien vers la situation de l'élève
    await page.locator('#btn-situation-eleve').click();
    await expect(page.locator('#eleve-bandeau')).toContainText('ADJO Sena');
  });

  test('changer d\'élève depuis la liste de recherche ; effacer revient à la recherche', async ({ page }) => {
    await ouvrirSaisiePaiement(page, MATRICULE);

    await page.locator('.recherche-eleve .select2-selection').click();
    await page.locator('.select2-container--open .select2-search__field').fill('E2E0001');
    await page.locator('.select2-results__option', { hasText: 'KOFFI Ama' }).click();
    await expect(page.locator('#eleve-bandeau')).toContainText('KOFFI Ama');

    await page.locator('.recherche-eleve .select2-selection__clear').click();
    await expect(page).toHaveURL(/\/finances\/paiements\/nouveau$/);
    await expect(page.locator('#invite-recherche')).toBeVisible();
  });

  test('un élève sans inscription sur l\'année de travail renvoie à la recherche avec un message', async ({ page }) => {
    await page.goto('/finances/paiements/999999');
    await expect(page).toHaveURL(/\/finances\/paiements\/nouveau$/);
    await expect(page.locator('#toast-container .toast-error')).toContainText('pas d\'inscription');
  });

  test('les totaux encaissés (année, mois, semaine, jour) ne sont visibles que de l\'Administrateur et de la Direction', async ({ page }) => {
    for (const profil of ['admin', 'directeur'] as const) {
      await seConnecter(page, profil);
      await page.goto('/finances/paiements');
      const totaux = page.locator('#totaux-encaisses');
      await expect(totaux, profil).toBeVisible();
      await expect(totaux).toContainText('Total des paiements encaissés');
      for (const [cle, libelle] of [['annee', 'Toute l\'année'], ['mois', 'Ce mois'], ['semaine', 'Cette semaine'], ['jour', 'Aujourd\'hui']]) {
        const cellule = totaux.locator(`[data-total="${cle}"]`);
        await expect(cellule).toContainText(libelle);
        await expect(cellule.locator('.total-valeur')).toContainText(/\d/);
      }
      // Le paiement encaissé plus haut (223 000) vient d'entrer en caisse : il compte dans le total du jour
      const duJour = (await totaux.locator('[data-total="jour"] .total-valeur').innerText()).replace(/\D/g, '');
      expect(Number(duJour), `total du jour vu par ${profil}`).toBeGreaterThanOrEqual(223000);
    }

    // La Direction consulte, sans saisir
    await expect(page.getByRole('link', { name: /Nouveau paiement/ })).toHaveCount(0);

    // Les autres profils qui ont accès à l'écran n'ont pas cette vue
    for (const profil of ['comptable', 'adjoint'] as const) {
      await seConnecter(page, profil);
      await page.goto('/finances/paiements');
      await expect(page.locator('table thead th').first(), profil).toBeVisible();
      await expect(page.locator('#totaux-encaisses'), profil).toHaveCount(0);
    }
  });

  test('le Caissier n\'a pas accès à la saisie des paiements', async ({ page }) => {
    await seConnecter(page, 'caissier');
    for (const chemin of ['/finances/paiements/nouveau', '/finances/paiements/eleves?q=adjo']) {
      const reponse = await page.request.get(chemin, { maxRedirects: 0 });
      expect(reponse.status(), chemin).toBe(403);
    }
  });
});
