import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, choisirEleve, expect, ouvrirSaisiePaiement, seConnecter, test, encaisserTout as encaisserLesPaiementsEnAttente } from '../support/test';

/**
 * Menu 4 — Services aux Élèves › 4.4 Activités & Événements
 * Profil concerné : Responsable Activités & Événements.
 *
 * Règle transversale « Trois régimes d'engagement » (§ 6) :
 *   1. activité : engagement souscrit avant tout paiement ;
 *   2. événement sans limite de places : achat libre, le paiement vaut participation ;
 *   3. événement à capacité limitée : pré-inscription, confirmée à l'encaissement.
 */
test.describe.configure({ mode: 'serial' });

const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

/** Ouvre l'écran de saisie d'un paiement pour un élève (profil Comptable). */
async function ouvrirPaiement(page: Page, matricule: string): Promise<void> {
  await ouvrirSaisiePaiement(page, matricule);
}

/** Le Caissier ouvre une caisse s'il n'en a pas, puis encaisse tout ce qui est en attente. */
async function encaisserTout(page: Page): Promise<void> {
  await seConnecter(page, 'caissier');
  await page.goto('/finances/caisses');
  if ((await page.locator('tbody tr').filter({ hasText: 'Ouverte' }).count()) === 0) {
    await page.locator('[data-bs-target="#caisseModal"]').click();
    const modale = page.locator('#caisseModal');
    await modale.locator('[name=libelle]').fill('Caisse activités');
    await modale.locator('[name=solde_initial]').fill('0');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);
  }

  await encaisserLesPaiementsEnAttente(page);
}

test.describe('4.4 Activités & Événements', () => {
  test('créer une activité parascolaire par niveau, avec son encadreur', async ({ page }) => {
    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/activites');

    for (const colonne of ['Activité', 'Type', 'Niveau', 'Encadreur', 'Montant', 'Inscrits']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    await page.locator('#btnNouvelleActivite').click();
    await page.locator('#ac-libelle').fill('Football');
    await page.locator('#ac-montant').fill('25000');
    await page.locator('#ac-type').selectOption({ label: 'Sportive' });
    await page.locator('#ac-niveau').selectOption({ label: 'CP' });
    await page.locator('#ac-encadreur').fill('M. AGBODJAN');
    await page.locator('#ac-contact').fill('90888888');
    await page.locator('#activite-form button[type=submit]').click();
    await attendreToast(page);

    const activite = ligneDe(page, 'Football');
    await expect(activite).toContainText('Sportive');
    await expect(activite).toContainText('CP');
    await expect(activite).toContainText('M. AGBODJAN');
    await expect(activite).toContainText(/25\s000/);

    await actionDeLigne(page, 'Football', 'Modifier');
    await expect(page.locator('#ac-libelle')).toHaveValue('Football');
    await expect(page.locator('#ac-encadreur')).toHaveValue('M. AGBODJAN');
    await page.locator('#ac-montant').fill('30000');
    await page.locator('#activite-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'Football')).toContainText(/30\s000/);
  });

  test('souscrire un élève à l\'activité, indépendamment de tout paiement', async ({ page }) => {
    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/activites');

    const activite = ligneDe(page, 'Football');
    await activite.locator('.btn-action').click();
    await activite.locator('.btn-souscrire').click();
    await expect(page.locator('#sc-activite-libelle')).toHaveText('Football');
    await choisirEleve(page, '#souscriptionModal', 'Ama', 'E2E0001');
    await page.locator('#souscription-form button[type=submit]').click();
    await attendreToast(page);

    // Le nombre d'inscrits suit
    await expect(ligneDe(page, 'Football').locator('td').nth(5)).toContainText('1');

    await page.goto('/services-eleves/activites-evenements/inscriptions');
    const inscription = ligneDe(page, 'KOFFI Ama');
    await expect(inscription).toContainText('Football');
    await expect(inscription).toContainText(/30\s000/);

    // Régime 1 : l'engagement « activité » est proposé au paiement, avec son reste dû
    await seConnecter(page, 'comptable');
    await ouvrirPaiement(page, 'E2E0001');
    await expect(page.locator('.engagement-row.exists').filter({ hasText: /activit/i })).toContainText(/30\s000,00/);
  });

  test('un élève ne peut pas être inscrit deux fois à la même activité', async ({ page }) => {
    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/activites');

    const activite = ligneDe(page, 'Football');
    await activite.locator('.btn-action').click();
    await activite.locator('.btn-souscrire').click();
    await choisirEleve(page, '#souscriptionModal', 'Ama', 'E2E0001');
    await page.locator('#souscription-form button[type=submit]').click();

    await expect(page.locator('#toast-container .toast-error').last()).toBeVisible();
    await page.goto('/services-eleves/activites-evenements/inscriptions');
    await expect(ligneDe(page, 'KOFFI Ama')).toHaveCount(1);
  });

  test('créer un événement à capacité limitée exige une date limite de confirmation', async ({ page }) => {
    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/evenements');

    await page.locator('#btnNouvelEvenement').click();
    await page.locator('#ev-nom').fill('Sortie au zoo');
    await page.locator('#ev-type').selectOption({ label: 'Sortie pédagogique' });
    await page.locator('#ev-date').fill('2026-12-10');
    await page.locator('#ev-participation').fill('5000');
    await page.locator('#ev-capacite').fill('2');
    await page.locator('#ev-cap-limitee').check();

    // Sans date limite : refusé
    await page.locator('#evenement-form button[type=submit]').click();
    await expect(page.locator('#evenementModal')).toBeVisible();
    await expect(page.locator('#date_limite_confirmation-error')).not.toBeEmpty();

    await page.locator('#ev-date-limite').fill('2026-12-01');
    await page.locator('#evenement-form button[type=submit]').click();
    await attendreToast(page);

    for (const colonne of ['Événement', 'Type', 'Date', 'Participation', 'Capacité', 'Remplissage']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    const evenement = ligneDe(page, 'Sortie au zoo');
    await expect(evenement).toContainText('Capacité limitée');
    await expect(evenement).toContainText('limite 01/12/2026');
    await expect(evenement).toContainText('10/12/2026');
    await expect(evenement).toContainText(/5\s000/);
    await expect(evenement).toContainText('0 / 2');
  });

  test('pré-inscrire un élève à l\'événement à capacité limitée, avant tout paiement', async ({ page }) => {
    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/evenements');

    const evenement = ligneDe(page, 'Sortie au zoo');
    await evenement.locator('.btn-action').click();
    await evenement.locator('.btn-pre-inscrire').click();
    await expect(page.locator('#pi-evt-nom')).toHaveText('Sortie au zoo');
    await choisirEleve(page, '#preInscriptionModal', 'Ama', 'E2E0001');
    await page.locator('#pre-inscription-form button[type=submit]').click();
    await attendreToast(page);

    // Une pré-inscription en attente, aucune place confirmée
    const maj = ligneDe(page, 'Sortie au zoo');
    await expect(maj).toContainText('0 / 2');
    await expect(maj).toContainText('1 pré-inscrit(s)');

    await maj.locator('a').first().click();
    const participant = ligneDe(page, 'KOFFI Ama');
    await expect(participant).toContainText('Pré-inscrit');
    await expect(participant).toContainText(/5\s000/);
  });

  test('régime 3 : l\'encaissement du paiement confirme automatiquement la pré-inscription', async ({ page }) => {
    // Le Comptable saisit le paiement de la pré-inscription (engagement conditionnel)
    await seConnecter(page, 'comptable');
    await ouvrirPaiement(page, 'E2E0001');
    const ligne = page.locator('.engagement-row.exists').filter({ hasText: 'Pré-inscription : Sortie au zoo' });
    await expect(ligne).toContainText(/5\s000,00/);
    await ligne.click();
    await expect(page.locator('#total-montant')).toHaveText(/5\s000,00/);
    await page.locator('#btn-submit').click();
    await attendreToast(page);

    // Tant que le paiement n'est pas encaissé, la place n'est pas confirmée
    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/evenements');
    await expect(ligneDe(page, 'Sortie au zoo')).toContainText('0 / 2');

    // Encaissement par le Caissier
    await encaisserTout(page);

    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/evenements');
    const evenement = ligneDe(page, 'Sortie au zoo');
    await expect(evenement).toContainText('1 / 2');
    await expect(evenement).toContainText('50 %');
    await evenement.locator('a').first().click();
    await expect(ligneDe(page, 'KOFFI Ama')).toContainText('Confirmé');
  });

  test('régime 2 : un événement sans limite de places s\'achète directement, le paiement vaut participation', async ({ page }) => {
    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/evenements');
    await page.locator('#btnNouvelEvenement').click();
    await page.locator('#ev-nom').fill('Kermesse de fin d\'année');
    await page.locator('#ev-type').selectOption({ label: 'Autre' });
    await page.locator('#ev-date').fill('2027-06-20');
    await page.locator('#ev-participation').fill('2000');
    await page.locator('#evenement-form button[type=submit]').click();
    await attendreToast(page);

    const kermesse = ligneDe(page, 'Kermesse');
    await expect(kermesse).toContainText('Illimitée');
    // Pas de pré-inscription pour un événement ouvert à tous
    await kermesse.locator('.btn-action').click();
    await expect(kermesse.locator('.btn-pre-inscrire')).toHaveCount(0);

    // Le Comptable le trouve dans le catalogue du panier ; l'événement à places limitées n'y figure pas
    await seConnecter(page, 'comptable');
    await ouvrirPaiement(page, 'E2E0002');
    await page.locator('#search-panier').fill('Sortie');
    await expect(page.locator('#search-results')).toContainText('Aucun résultat');
    await page.locator('#search-panier').fill('Kermesse');
    await page.locator('#search-results .search-result-item', { hasText: 'Kermesse' }).click();
    await expect(page.locator('#total-montant')).toHaveText(/2\s000,00/);
    await page.locator('#btn-submit').click();
    await attendreToast(page);

    await encaisserTout(page);

    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/evenements');
    await expect(ligneDe(page, 'Kermesse')).toContainText('1 confirmé(s)');
    await ligneDe(page, 'Kermesse').locator('a').first().click();
    await expect(ligneDe(page, 'KOFFI Kossi')).toContainText('Confirmé');
  });

  test('annuler une inscription à une activité éteint l\'engagement', async ({ page }) => {
    await seConnecter(page, 'activites');
    await page.goto('/services-eleves/activites-evenements/inscriptions');

    const inscription = ligneDe(page, 'KOFFI Ama');
    await inscription.locator('.btn-action').click();
    await inscription.locator('.btn-annuler-insc').click();
    const motif = page.locator('#annuler-inscription-form textarea');
    if (await motif.count()) await motif.fill('Blessure, dispense de sport');
    await page.locator('#annuler-inscription-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'KOFFI Ama')).toContainText('Annulée');

    await seConnecter(page, 'comptable');
    await ouvrirPaiement(page, 'E2E0001');
    await expect(page.locator('.engagement-row.exists').filter({ hasText: /activit/i })).toHaveCount(0);
  });

  test('le tableau de bord des activités et les exports', async ({ page }) => {
    await seConnecter(page, 'activites');
    await page.goto('/tableau');
    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Services aux Élèves' }).first();
    await menu.hover();
    const lien = menu.locator('.hnav-drop-item', { hasText: 'Tableau de bord activités' });
    // Menu long : le groupe ouvre ses écrans dans un second niveau
    const groupe = menu.locator('.hnav-sub').filter({ has: page.locator('.hnav-drop-item', { hasText: 'Tableau de bord activités' }) });
    if (await groupe.count()) await groupe.hover();
    await lien.click();
    await expect(page.locator('main.content-area')).toBeVisible();
    await expect(page.locator('main')).toContainText('Sortie au zoo');

    for (const lien of await page.locator('a[href*="/export"]').evaluateAll((as) => as.map((a) => (a as HTMLAnchorElement).href))) {
      expect((await page.request.get(lien)).status(), lien).toBe(200);
    }
  });
});
