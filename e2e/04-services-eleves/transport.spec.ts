import type { Page } from '@playwright/test';
import { actionDeLigne, attendreToast, choisirEleve, expect, ouvrirSaisiePaiement, seConnecter, test } from '../support/test';

/**
 * Menu 4 — Services aux Élèves › 4.1 Transport scolaire
 * Profil concerné : Responsable Transport scolaire.
 * Règle transversale « Souscription indépendante du paiement » (§ 6) :
 * l'abonnement bus crée le dû sans attendre un paiement.
 */
test.describe.configure({ mode: 'serial' });

const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });

test.describe('4.1 Transport scolaire', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'transport');
  });

  test('gérer le parc : créer un véhicule puis le modifier', async ({ page }) => {
    await page.goto('/services-eleves/transport/voitures');

    await page.locator('#btnNouvelleVoiture').click();
    await page.locator('#v-plaque').fill('TG-1234-AB');
    await page.locator('#v-marque').fill('Toyota');
    await page.locator('#v-modele').fill('Coaster');
    await page.locator('#v-annee').fill('2021');
    await page.locator('#v-places').fill('30');
    await page.locator('#v-km').fill('45000');
    await page.locator('#v-statut').selectOption({ label: 'Disponible' });
    await page.locator('#voiture-form button[type=submit]').click();
    await attendreToast(page);

    for (const colonne of ['Plaque', 'Chauffeur affecté', 'Zones desservies', 'Kilométrage', 'Statut']) {
      await expect(page.locator('thead th').filter({ hasText: new RegExp(colonne, 'i') }).first()).toBeVisible();
    }
    const vehicule = ligneDe(page, 'TG-1234-AB');
    await expect(vehicule).toContainText('Toyota');
    await expect(vehicule).toContainText('Coaster');
    await expect(vehicule).toContainText('Disponible');
    await expect(vehicule).toContainText(/45\s?000/);

    await actionDeLigne(page, 'TG-1234-AB', 'Modifier');
    await expect(page.locator('#v-plaque')).toHaveValue('TG-1234-AB');
    await expect(page.locator('#v-places')).toHaveValue('30');
    await page.locator('#v-km').fill('45200');
    await page.locator('#voiture-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'TG-1234-AB')).toContainText(/45\s?200/);
  });

  test('deux véhicules ne peuvent pas porter la même plaque', async ({ page }) => {
    await page.goto('/services-eleves/transport/voitures');
    await page.locator('#btnNouvelleVoiture').click();
    await page.locator('#v-plaque').fill('TG-1234-AB');
    await page.locator('#v-marque').fill('Doublon');
    await page.locator('#voiture-form button[type=submit]').click();

    await expect(page.locator('#voitureModal')).toBeVisible();
    await expect(page.locator('#voitureModal [id$="-error"]').filter({ hasText: /\S/ }).or(page.locator('#toast-container .toast-error')).first()).toBeVisible();
  });

  test('créer un chauffeur et l\'affecter au véhicule', async ({ page }) => {
    await page.goto('/services-eleves/transport/chauffeurs');
    await page.locator('#btnNouveauChauffeur').click();
    await page.locator('#c-nom').fill('SOSSOU');
    await page.locator('#c-prenom').fill('Komlan');
    await page.locator('#c-permis').fill('PC-778899');
    await page.locator('#c-validite').fill('2028-06-30');
    await page.locator('#c-telephone').fill('90777777');
    await page.locator('#chauffeur-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'SOSSOU')).toContainText('Komlan');

    // Affectation depuis la fiche du véhicule
    await page.goto('/services-eleves/transport/voitures');
    await ligneDe(page, 'TG-1234-AB').locator('a').first().click();
    await page.locator('[data-bs-target="#affecterModal"]').click();
    const modale = page.locator('#affecterModal');
    const chauffeur = await modale.locator('[name=chauffeur_id] option', { hasText: 'SOSSOU' }).getAttribute('value');
    await modale.locator('[name=chauffeur_id]').selectOption(chauffeur!);
    await modale.locator('[name=motif]').fill('Affectation de rentrée');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);
    await expect(page.locator('main')).toContainText('SOSSOU');

    // La liste des véhicules montre le chauffeur affecté
    await page.goto('/services-eleves/transport/voitures');
    await expect(ligneDe(page, 'TG-1234-AB')).toContainText('SOSSOU');
  });

  test('paramétrer une ligne (chauffeur, véhicule, période) puis une de ses zones', async ({ page }) => {
    await page.goto('/services-eleves/transport/lignes');
    await page.locator('#btnNouvelleLigne').click();
    await page.locator('#l-libelle').fill('Ligne 1 — Agoè / École');
    const chauffeur = await page.locator('#l-chauffeur option', { hasText: 'SOSSOU' }).getAttribute('value');
    await page.locator('#l-chauffeur').selectOption(chauffeur!);
    const voiture = await page.locator('#l-voiture option', { hasText: 'TG-1234-AB' }).getAttribute('value');
    await page.locator('#l-voiture').selectOption(voiture!);
    await page.locator('#l-debut').fill('2026-09-15');
    await page.locator('#l-fin').fill('2027-07-15');
    await page.locator('#ligne-form button[type=submit]').click();
    await attendreToast(page);
    const ligne = ligneDe(page, 'Ligne 1');
    await expect(ligne).toContainText('SOSSOU');
    await expect(ligne).toContainText('15/09/2026');

    await page.goto('/services-eleves/transport/zones');
    await page.locator('#btnNouvelleZone').click();
    await page.locator('#z-libelle').fill('Zone Nord — Agoè');
    await page.locator('#z-description').fill('Agoè, Adidogomé');
    const idLigne = await page.locator('#z-ligne option', { hasText: 'Ligne 1' }).getAttribute('value');
    await page.locator('#z-ligne').selectOption(idLigne!);
    await page.locator('#z-tarif').fill('15000');
    await page.locator('#zone-form button[type=submit]').click();
    await attendreToast(page);
    const zone = ligneDe(page, 'Zone Nord');
    await expect(zone).toContainText('Ligne 1');
    await expect(zone).toContainText('SOSSOU');
    await expect(zone).toContainText('TG-1234-AB');
    await expect(zone).toContainText(/15\s?000/);
  });

  test('enregistrer un entretien, un plein de carburant et une assurance pour le véhicule', async ({ page }) => {
    // Entretien
    await page.goto('/services-eleves/transport/entretiens');
    await page.locator('#btnNouvelEntretien').click();
    let voiture = await page.locator('#e-voiture option', { hasText: 'TG-1234-AB' }).getAttribute('value');
    await page.locator('#e-voiture').selectOption(voiture!);
    await page.locator('#e-type').fill('Vidange');
    await page.locator('#e-cout').fill('35000');
    await page.locator('#e-km').fill('45500');
    await page.locator('#entretien-form button[type=submit]').click();
    await attendreToast(page);
    const entretien = ligneDe(page, 'Vidange');
    await expect(entretien).toContainText('TG-1234-AB');
    await expect(entretien).toContainText(/35\s?000/);

    // Carburant : 40 L à 700 = 28 000
    await page.goto('/services-eleves/transport/carburants');
    await page.locator('#btnNouveauPlein').click();
    voiture = await page.locator('#cb-voiture option', { hasText: 'TG-1234-AB' }).getAttribute('value');
    await page.locator('#cb-voiture').selectOption(voiture!);
    await page.locator('#cb-km').fill('45800');
    await page.locator('#cb-litres').fill('40');
    await page.locator('#cb-prix').fill('700');
    await page.locator('#cb-station').fill('Total Agoè');
    await page.locator('#carburant-form button[type=submit]').click();
    await attendreToast(page);
    const plein = ligneDe(page, 'Total Agoè');
    await expect(plein).toContainText('TG-1234-AB');
    await expect(plein).toContainText(/28\s?000/);

    // Assurance
    await page.goto('/services-eleves/transport/assurances');
    await page.locator('#btnNouvelleAssurance').click();
    voiture = await page.locator('#as-voiture option', { hasText: 'TG-1234-AB' }).getAttribute('value');
    await page.locator('#as-voiture').selectOption(voiture!);
    await page.locator('#as-compagnie').fill('NSIA Assurances');
    await page.locator('#as-contrat').fill('AUTO-2026-555');
    await page.locator('#as-debut').fill('2026-09-01');
    await page.locator('#as-fin').fill('2027-08-31');
    await page.locator('#as-prime').fill('180000');
    await page.locator('#as-type').fill('Tous risques');
    await page.locator('#assurance-form button[type=submit]').click();
    await attendreToast(page);
    const assurance = ligneDe(page, 'NSIA Assurances');
    await expect(assurance).toContainText('AUTO-2026-555');
    await expect(assurance).toContainText('31/08/2027');

    // La fiche du véhicule rassemble l'historique ; le kilométrage suit le dernier relevé
    await page.goto('/services-eleves/transport/voitures');
    await expect(ligneDe(page, 'TG-1234-AB')).toContainText(/45\s?800/);
    await ligneDe(page, 'TG-1234-AB').locator('a').first().click();
    await expect(page.locator('main')).toContainText('Vidange');
    await expect(page.locator('main')).toContainText('NSIA Assurances');
  });

  test('souscrire un élève à un abonnement bus : le dû apparaît aussitôt, sans paiement', async ({ page }) => {
    await page.goto('/services-eleves/transport/abonnements');

    await page.locator('#btnNouvelAbonnement').click();
    const modale = page.locator('#abonnementModal');
    await choisirEleve(page, '#abonnementModal', 'Ama', 'E2E0001');
    // On choisit la zone seule : son tarif est proposé ; ligne et véhicule en découlent
    const zone = await modale.locator('[name=zone_id] option', { hasText: 'Zone Nord' }).getAttribute('value');
    await modale.locator('[name=zone_id]').selectOption(zone!);
    await expect(modale.locator('[name=montant_mensuel]')).toHaveValue('15000');
    await expect(modale.locator('[name=voiture_id], [name=ligne_id], [name=point_arret]')).toHaveCount(0);
    await modale.locator('[name=nombre_mois]').fill('9');
    await modale.locator('[name=sens]').selectOption({ label: 'Aller-retour' });
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    // 15 000 × 9 mois = 135 000
    const abonnement = ligneDe(page, 'KOFFI Ama');
    await expect(abonnement).toContainText('E2E0001');
    await expect(abonnement).toContainText('Zone Nord');
    await expect(abonnement).toContainText('TG-1234-AB');
    await expect(abonnement).toContainText('Aller-retour');
    await expect(abonnement).toContainText(/15\s000/);
    await expect(abonnement).toContainText(/135\s000/);
    await expect(abonnement).toContainText('Actif');

    // Le dû est visible du Comptable sur l'écran Paiements, sans qu'aucun paiement n'existe
    await seConnecter(page, 'comptable');
    await ouvrirSaisiePaiement(page, 'E2E0001');
    const bus = page.locator('.engagement-row.exists').filter({ hasText: /bus|transport/i });
    await expect(bus).toContainText(/135\s000,00/);
  });

  test('un élève ne peut pas avoir deux abonnements bus actifs', async ({ page }) => {
    await page.goto('/services-eleves/transport/abonnements');
    await page.locator('#btnNouvelAbonnement').click();
    const modale = page.locator('#abonnementModal');
    await choisirEleve(page, '#abonnementModal', 'Ama', 'E2E0001');
    await modale.locator('[name=montant_mensuel]').fill('15000');
    await modale.locator('button[type=submit]').click();

    await expect(page.locator('#toast-container .toast-error').last()).toBeVisible();
    await page.reload();
    await expect(ligneDe(page, 'KOFFI Ama')).toHaveCount(1);
  });

  test('changer le trajet d\'un abonnement, renseigner la position GPS du domicile, puis enregistrer un abandon', async ({ page }) => {
    await page.goto('/services-eleves/transport/abonnements');

    let abonnement = ligneDe(page, 'KOFFI Ama');
    await abonnement.locator('.btn-action').click();
    await abonnement.locator('.btn-assigner').click();
    await page.locator('#assign-sens').selectOption({ label: 'Aller simple' });
    await page.locator('#assigner-form button[type=submit]').click();
    await attendreToast(page);

    abonnement = ligneDe(page, 'KOFFI Ama');
    await expect(abonnement).toContainText('Aller simple');

    // Position GPS du domicile, renseignée après la souscription : on colle « latitude, longitude »
    await abonnement.locator('.btn-action').click();
    await abonnement.locator('.btn-position').click();
    await page.locator('#position-coller').fill('6.1725, 1.2314');
    await expect(page.locator('#position-lat')).toHaveValue('6.1725');
    await expect(page.locator('#position-lng')).toHaveValue('1.2314');
    await page.locator('#position-form button[type=submit]').click();
    await attendreToast(page);
    abonnement = ligneDe(page, 'KOFFI Ama');
    await expect(abonnement.getByRole('link', { name: /Domicile sur la carte/ })).toHaveAttribute('href', /6\.1725.*1\.2314/);

    await abonnement.locator('.btn-action').click();
    await abonnement.locator('.btn-abandonner').click();
    await page.locator('#abandon-form [name=motif]').fill('La famille a déménagé près de l\'école');
    await page.locator('#abandon-form button[type=submit]').click();
    await attendreToast(page);
    await expect(ligneDe(page, 'KOFFI Ama')).toContainText('Abandonné');

    // L'abandon éteint l'engagement : plus de dû « bus » pour le Comptable
    await seConnecter(page, 'comptable');
    await ouvrirSaisiePaiement(page, 'E2E0001');
    await expect(page.locator('.engagement-row.exists').filter({ hasText: /bus|transport/i })).toHaveCount(0);
  });

  test('le tableau de bord transport et les exports', async ({ page }) => {
    await page.goto('/tableau');
    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Services aux Élèves' }).first();
    await menu.hover();
    const lien = menu.locator('.hnav-drop-item', { hasText: 'Tableau de bord transport' });
    // Menu long : le groupe ouvre ses écrans dans un second niveau
    const groupe = menu.locator('.hnav-sub').filter({ has: page.locator('.hnav-drop-item', { hasText: 'Tableau de bord transport' }) });
    if (await groupe.count()) await groupe.hover();
    await lien.click();
    await expect(page.locator('main.content-area')).toBeVisible();
    await expect(page.locator('main')).toContainText(/Véhicule|véhicule/);

    for (const chemin of ['/services-eleves/transport/voitures/export', '/services-eleves/transport/abonnements/export']) {
      expect((await page.request.get(chemin)).status(), chemin).toBe(200);
    }
  });

  test('les autres responsables de service n\'ont pas accès au transport', async ({ page }) => {
    await seConnecter(page, 'cantine');
    const reponse = await page.request.get('/services-eleves/transport/voitures', { maxRedirects: 0 });
    expect(reponse.status()).toBe(403);
  });
});
