import type { Page } from '@playwright/test';
import { attendreToast, expect, ouvrirSaisiePaiement, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité : 2.1 (souscription), 2.2 Paiements,
 * 2.3 Encaissements, 2.4 Caisses, et les règles transversales du § 6 :
 *   - « Souscription indépendante du paiement » ;
 *   - « Cycle de vie d'un paiement » (le Comptable saisit, le Caissier encaisse) ;
 *   - « Gestion des caisses et des écarts » (écart motivé, validé par la Direction) ;
 *   - « Journal de caisse » (figé à la clôture).
 *
 * Les tests de ce fichier s'enchaînent : ils racontent une journée de caisse.
 */
test.describe.configure({ mode: 'serial' });

const ELEVE = 'KOFFI Ama';
const MATRICULE = 'E2E0001';
const CAISSE = 'Caisse E2E principale';

async function ouvrirFichePaiement(page: Page): Promise<void> {
  await ouvrirSaisiePaiement(page, MATRICULE);
}

test.describe('Circuit du paiement : dû de l\'inscription → paiement → encaissement → clôture → écart', () => {
  test('la scolarité vient de l\'inscription, remise comprise : le dû existe sans souscription ni paiement', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await ouvrirFichePaiement(page);

    // 469 000 − 20 % de remise accordée à l'inscription = 375 200
    const engagement = page.locator('.engagement-row.exists').first();
    await expect(engagement).toContainText('375 200,00');
    await expect(engagement).toContainText('reste dû');
  });

  test('l\'écran Souscriptions ne porte que les services : la scolarité n\'y est pas proposée', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/souscriptions');

    for (const colonne of ['Élève', 'Souscription', 'Montant', 'Règlement', 'Actions']) {
      await expect(page.locator('#table-souscriptions thead th').filter({ hasText: colonne })).toHaveCount(1);
    }
    await expect(page.locator('#table-souscriptions thead th')).toHaveCount(5);

    await page.locator('[data-bs-target="#souscModal"]').click();
    const modale = page.locator('#souscModal');
    await expect(modale.locator('[name=frais_ecole_id] option', { hasText: 'Cantine CP' })).toHaveCount(1);
    await expect(modale.locator('[name=frais_ecole_id] option', { hasText: 'Scolarité CP' })).toHaveCount(0);

    // Le tableau de bord n'est pas montré au Comptable
    await expect(page.locator('#tableau-de-bord')).toHaveCount(0);
  });

  test('le tableau de bord des souscriptions est réservé à la Direction et à l\'Administrateur', async ({ page }) => {
    for (const profil of ['directeur', 'admin'] as const) {
      await seConnecter(page, profil);
      await page.goto('/finances/souscriptions');

      const tableau = page.locator('#tableau-de-bord');
      await expect(tableau.locator('.service-card')).toHaveCount(4);
      for (const service of ['cantine', 'transport', 'événement', 'activité']) {
        await expect(tableau).toContainText(`souscription(s) ${service}`);
      }
      await expect(tableau.locator('.service-card').first()).toContainText('Montant attendu');
      await expect(tableau.locator('.service-card').first()).toContainText('Reste à percevoir');
    }

    // La Direction consulte mais ne crée pas de souscription
    await seConnecter(page, 'directeur');
    await page.goto('/finances/souscriptions');
    await expect(page.locator('[data-bs-target="#souscModal"]')).toHaveCount(0);
  });

  test('le Comptable enregistre un paiement combinant l\'écolage et un produit du panier', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await ouvrirFichePaiement(page);

    // Zone 1 : engagement existant
    await page.locator('.engagement-row.exists').first().click();
    await expect(page.locator('#total-montant')).toContainText('375');

    // Achat libre d'un produit en stock : la quantité se règle dans le récapitulatif
    await page.locator('#search-panier').fill('T-shirt');
    await page.locator('#search-results .search-result-item', { hasText: 'T-shirt M' }).click();
    await page.locator('.panier-qte').fill('2');

    // Zone 3 : 375 200 + 2 × 6 500 = 388 200
    await expect(page.locator('#panier-body tr')).toHaveCount(2);
    await expect(page.locator('#total-lignes')).toHaveText('2');
    await expect(page.locator('#total-montant')).toHaveText(/388\s200,00/);

    await page.locator('[name=mode_paiement]').selectOption({ label: 'Espèces' });
    await page.locator('#btn-submit').click();
    await attendreToast(page);

    // Le paiement est enregistré, pas encore encaissé : le reste dû n'a pas bougé
    await page.goto('/finances/paiements');
    const ligne = page.locator('tbody tr').filter({ hasText: ELEVE }).first();
    await expect(ligne).toContainText('En attente d\'encaissement');
    await expect(ligne).toContainText(/388\s200,00/);
    await expect(ligne).toContainText('Espèces');
    await expect(ligne.locator('.reference')).not.toBeEmpty();
  });

  test('le Comptable n\'encaisse pas : l\'écran Encaissements lui est refusé', async ({ page }) => {
    await seConnecter(page, 'comptable');
    const reponse = await page.request.get('/finances/encaissements', { maxRedirects: 0 });
    expect(reponse.status()).toBe(403);
  });

  test('sans caisse ouverte, le Caissier voit les paiements en attente mais ne peut pas encaisser', async ({ page }) => {
    await seConnecter(page, 'caissier');
    await page.goto('/finances/encaissements');

    // Pas de bouton d'ouverture de caisse sur cet écran ; sans caisse ouverte, pas d'onglet « Caisse ouverte »
    // (ni « Nouvel encaissement », ni journal) : le caissier arrive sur « Mes encaissements »
    await expect(page.locator('#caisse-fermee')).toBeVisible();
    await expect(page.getByRole('link', { name: /Ouvrir une caisse/ })).toHaveCount(0);
    await expect(page.locator('#onglet-caisse, #btn-nouvel-encaissement, #btn-journal-caisse')).toHaveCount(0);
    await expect(page.locator('#onglet-mes')).toHaveClass(/active/);

    await page.locator('#onglet-attente').click();
    const ligne = page.locator('#table-attente tbody tr').filter({ hasText: ELEVE });
    await expect(ligne).toBeVisible();
    await expect(ligne.locator('.btn-encaisser-action')).toBeDisabled();

    // Le serveur refuse aussi l'encaissement
    const id = await ligne.getAttribute('data-paiement');
    const jeton = await page.locator('meta[name=csrf-token]').getAttribute('content');
    const reponse = await page.request.post(`/finances/encaissements/paiement/${id}/encaisser`, {
      headers: { 'X-CSRF-TOKEN': jeton!, Accept: 'application/json' },
      form: { montant_recu: '388200' },
    });
    expect(reponse.status()).toBe(422);
    expect((await reponse.json()).message).toContain('pas de caisse ouverte');
  });

  test('le Caissier ouvre sa caisse en déclarant le solde initial', async ({ page }) => {
    await seConnecter(page, 'caissier');
    await page.goto('/finances/caisses');

    // Le tableau de bord est réservé à l'Administrateur et à la Direction ; le caissier voit « Mes caisses »
    await expect(page.locator('#compteur-caisses')).toHaveCount(0);
    await expect(page.locator('#onglet-mes-caisses')).toHaveClass(/active/);
    await expect(page.locator('#onglet-toutes-caisses')).toHaveCount(0);

    await page.locator('[data-bs-target="#caisseModal"]').click();
    const modale = page.locator('#caisseModal');
    // Le caissier ouvre sa propre caisse : pas de choix du responsable
    await expect(modale.locator('[name=responsable_id]')).toHaveCount(0);
    await expect(modale.locator('#responsable-moi')).toContainText('(moi)');
    await modale.locator('[name=libelle]').fill(CAISSE);
    await modale.locator('[name=solde_initial]').fill('10000');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    const ligne = page.locator('tbody tr').filter({ hasText: CAISSE });
    await expect(ligne).toContainText('Ouverte');
    await expect(ligne).toContainText('10 000,00');
    await expect(ligne).toContainText('E2E Caissier');

    // Une seconde caisse lui est refusée, avec le motif
    await page.locator('[data-bs-target="#caisseModal"]').click();
    await modale.locator('[name=libelle]').fill('Caisse en trop');
    await modale.locator('[name=solde_initial]').fill('0');
    await modale.locator('button[type=submit]').click();
    await expect(page.locator('#toast-container .toast-error').last()).toContainText('déjà une caisse ouverte');
  });

  test('l\'Administrateur voit le tableau de bord des caisses et peut confier une caisse ou la prendre lui-même', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/finances/caisses');

    // Tableau de bord : caisses de l'année (ouvertes, clôturées), et caisses ouvertes / clôturées du mois, de la semaine, du jour
    await expect(page.locator('#compteur-caisses')).toContainText('caisse(s)');
    await expect(page.locator('#compteur-statuts')).toContainText(/\d+ ouverte\(s\) · .*\d+ clôturée\(s\)/);
    const ouvertes = Number(/(\d+) ouverte/.exec(await page.locator('#compteur-statuts').innerText())![1]);
    expect(ouvertes).toBeGreaterThanOrEqual(1);
    for (const periode of ['mois', 'semaine', 'jour']) {
      await expect(page.locator(`#total-${periode}`)).toContainText(/\d+ ouverte\(s\) · \d+ clôturée\(s\)/);
    }
    // Deux onglets ; « Toutes les caisses » par défaut, filtrable par caissier
    await expect(page.locator('#onglet-toutes-caisses')).toHaveClass(/active/);
    await expect(page.locator('#onglet-mes-caisses')).toBeVisible();
    await expect(page.locator('#filtre-caissier-caisses')).toBeVisible();

    await page.locator('[data-bs-target="#caisseModal"]').click();
    const responsable = page.locator('#caisseModal [name=responsable_id]');
    await expect(responsable.locator('option:checked')).toContainText('(moi)');
    await expect(responsable).toContainText('Caissier');
    await page.locator('#caisseModal .btn-annuler').click();
  });

  test('le Caissier encaisse le paiement avec le montant reçu ; le reçu s\'imprime depuis ses encaissements du jour', async ({ page }) => {
    await seConnecter(page, 'caissier');
    await page.goto('/finances/encaissements');
    await expect(page.locator('#caisse-ouverte')).toContainText(CAISSE);

    // « Encaisser » sur le paiement en attente : son détail (écolage et produit) et le montant à saisir
    await page.locator('#onglet-attente').click();
    await page.locator('#table-attente tbody tr').filter({ hasText: ELEVE }).locator('.btn-encaisser-action').click();
    const modale = page.locator('#encaisserModal');
    await expect(modale.locator('.resume-lignes tbody tr')).toHaveCount(2);
    await expect(modale.locator('#enc-du')).toHaveText(/388\s200/);
    await expect(modale.locator('#enc-montant-recu')).toHaveValue('388200');

    // Montant insuffisant : refusé ; montant supérieur : monnaie à rendre
    await modale.locator('#enc-montant-recu').fill('300000');
    await expect(modale.locator('#enc-ecart')).toContainText('Il manque');
    await expect(modale.locator('#enc-valider')).toBeDisabled();
    await modale.locator('#enc-montant-recu').fill('400000');
    await expect(modale.locator('#enc-ecart')).toContainText(/Monnaie à rendre : 11\s800/);
    await modale.locator('#enc-valider').click();

    // Fenêtre fermée, notification, retour sur « Caisse ouverte »
    await expect(page).toHaveURL(/onglet=caisse/);
    await attendreToast(page, 'Encaissement effectué');
    await expect(page.locator('#toast-container .toast-item').last()).toContainText(/11\s800/);
    await expect(page.locator('#encaisserModal')).toBeHidden();

    const jour = page.locator('#table-caisse tbody tr').filter({ hasText: ELEVE });
    await expect(jour).toHaveCount(1);
    await expect(jour).toContainText(CAISSE);
    await expect(jour).toContainText(/388\s200/);
    await expect(jour).toContainText(/rendu 11\s800/);

    // Le reçu s'imprime à tout moment
    const lien = await jour.locator('.btn-imprimer-recu').getAttribute('href');
    const pdf = await page.request.get(lien!);
    expect(pdf.status()).toBe(200);
    expect(pdf.headers()['content-type']).toContain('pdf');
  });

  test('après encaissement, le paiement est « Encaissé » et le reste dû de l\'élève est soldé', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/paiements');
    await expect(page.locator('tbody tr').filter({ hasText: ELEVE }).first()).toContainText('Encaissé');

    // L'écolage est soldé : il ne reste plus rien à payer sur cet engagement
    await ouvrirFichePaiement(page);
    await expect(page.locator('.engagement-row.exists')).toHaveCount(0);

    // Le produit vendu par le panier est sorti du stock à l'encaissement (§ 6) : 20 − 2 = 18
    await seConnecter(page, 'achats');
    await page.goto('/logistique/produits');
    await expect(page.locator('tbody tr').filter({ hasText: 'T-shirt M' }).locator('td.num.grand')).toHaveText('18,00');

    // Liste des élèves (§ 1.1) : total encaissé mis à jour
    await seConnecter(page, 'secretaire');
    await page.goto('/scolarite/eleves');
    await expect(page.locator('tbody tr').filter({ hasText: MATRICULE })).toContainText(/388\s200|375\s200/);
  });

  test('le Caissier enregistre un mouvement de caisse hors encaissement', async ({ page }) => {
    await seConnecter(page, 'caissier');
    await page.goto('/finances/caisses');
    const ligne = page.locator('tbody tr').filter({ hasText: CAISSE });
    await ligne.locator('.btn-action').click();
    await ligne.getByRole('link', { name: /journal/i }).click();

    await page.locator('[data-bs-target="#mouvementModal"]').click();
    const modale = page.locator('#mouvementModal');
    await modale.locator('[name=type_mouvement]').selectOption({ index: 1 });
    await modale.locator('[name=libelle]').fill('Achat de craies');
    await modale.locator('[name=montant]').fill('2000');
    await modale.locator('[name=beneficiaire]').fill('Librairie du Centre');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    // Le journal se remplit en temps réel, session encore ouverte
    const journal = page.locator('#table-mouvements tbody');
    await expect(journal).toContainText('Achat de craies');
    await expect(journal).toContainText('Librairie du Centre');
    await expect(journal).toContainText(/2\s000,00/);

    // La sortie figure aussi dans l'onglet des décaissements
    await page.locator('#onglet-decaissements').click();
    const decaissement = page.locator('#table-decaissements tbody tr').filter({ hasText: 'Achat de craies' });
    await expect(decaissement).toContainText('Sortie manuelle');
    await expect(decaissement).toContainText('Librairie du Centre');
    await expect(page.locator('#total-decaisse')).toContainText(/2\s000,00/);
  });

  test('le journal de caisse se consulte et s\'exporte pendant que la session est ouverte', async ({ page }) => {
    // « Toutes les caisses » : Direction et Administrateur
    await seConnecter(page, 'directeur');
    await page.goto('/finances/caisses');
    const ligne = page.locator('tbody tr').filter({ hasText: CAISSE });
    await ligne.locator('.btn-action').click();
    await ligne.getByRole('link', { name: /journal/i }).click();

    await expect(page.locator('.page-title-main')).toContainText('Journal de caisse');
    await expect(page.locator('main')).toContainText('KOFFI');

    // Trois onglets : la chronologie, les encaissements (avec totaux par type) et les décaissements
    await expect(page.locator('#onglet-mouvements')).toHaveClass(/active/);
    await expect(page.locator('#table-mouvements tbody tr')).toHaveCount(3);   // 2 encaissements + 1 sortie

    await page.locator('#onglet-encaissements').click();
    await expect(page).toHaveURL(/onglet=encaissements/);
    const totaux = page.locator('#totaux-par-type');
    await expect(totaux).toContainText('Produit');
    await expect(totaux).toContainText(/13\s000,00/);                           // 2 T-shirts à 6 500
    await expect(page.locator('#total-encaisse')).toContainText(/388\s200,00/);
    const encaissement = page.locator('#table-encaissements tbody tr').filter({ hasText: 'KOFFI' });
    await expect(encaissement).toHaveCount(1);
    await expect(encaissement).toContainText(/388\s200,00/);

    await page.locator('#onglet-decaissements').click();
    await expect(page.locator('#table-decaissements tbody')).toContainText('Achat de craies');

    // Le Comptable consulte : ni mouvement, ni clôture
    await expect(page.locator('[data-bs-target="#mouvementModal"]')).toHaveCount(0);
    await expect(page.locator('[data-bs-target="#clotureSessionModal"]')).toHaveCount(0);

    const telechargement = page.waitForEvent('download');
    await page.getByRole('link', { name: /Excel|Export/i }).first().click();
    expect((await telechargement).suggestedFilename()).toMatch(/\.(xlsx|csv)$/);

    const pdf = await page.request.get(await page.getByRole('link', { name: 'PDF' }).getAttribute('href').then((h) => h!));
    expect(pdf.status()).toBe(200);
  });

  test('à la clôture, un écart doit être motivé ; le journal est alors figé', async ({ page }) => {
    await seConnecter(page, 'caissier');
    await page.goto('/finances/caisses');
    const ligne = page.locator('tbody tr').filter({ hasText: CAISSE });
    await ligne.locator('.btn-action').click();
    await ligne.getByRole('link', { name: /journal/i }).click();

    await page.locator('[data-bs-target="#clotureSessionModal"]').click();
    const modale = page.locator('#clotureSessionModal');
    const theorique = (await modale.locator('.fs-5').innerText()).replace(/\s/g, '').replace(',', '.');
    const compte = Number(theorique) - 500;

    // Écart de −500 : le motif devient obligatoire
    await modale.locator('[name=solde_compte]').fill(String(compte));
    await expect(modale.locator('#cloture-session-ecart')).toBeVisible();
    await expect(modale.locator('#cloture-session-ecart-valeur')).toContainText('500');
    await modale.locator('[name=motif_ecart]').fill('Erreur de rendu de monnaie');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    // Session clôturée : plus aucune écriture possible
    await expect(page.locator('main')).toContainText(/Clôturée/i);
    await expect(page.locator('[data-bs-target="#mouvementModal"]')).toHaveCount(0);
    await expect(page.locator('[data-bs-target="#clotureSessionModal"]')).toHaveCount(0);

    await page.goto('/finances/caisses');
    const cloturee = page.locator('tbody tr').filter({ hasText: CAISSE });
    await expect(cloturee).toContainText('Clôturée');
    await expect(cloturee).toContainText('500,00');
  });

  test('le Caissier ne peut pas valider son propre écart', async ({ page }) => {
    await seConnecter(page, 'caissier');
    const reponse = await page.request.get('/finances/caisses/ecarts', { maxRedirects: 0 });
    expect(reponse.status()).toBe(403);
  });

  test('la Direction statue sur l\'écart : un refus exige un commentaire, puis l\'écart est validé', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/finances/caisses/ecarts');

    const ligne = page.locator('tbody tr').filter({ hasText: CAISSE });
    await expect(ligne).toContainText('Erreur de rendu de monnaie');
    await expect(ligne).toContainText('500,00');

    await ligne.locator('.btn-statuer').click();
    const modale = page.locator('#statuerModal');
    await expect(modale.locator('#statuer-libelle')).toHaveText(CAISSE);
    await expect(modale.locator('#statuer-motif')).toHaveText('Erreur de rendu de monnaie');

    // Refus sans commentaire : bloqué
    await modale.locator('.decision-radio[value=refuse]').check();
    await expect(modale.locator('#commentaire-requis')).toBeVisible();
    await modale.locator('button[type=submit]').click();
    await expect(modale).toBeVisible();

    // Validation
    await modale.locator('.decision-radio[value=valide]').check();
    await modale.locator('#statuer-commentaire').fill('Écart mineur, justification acceptée');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);

    await page.goto('/finances/caisses/ecarts?statut_ecart=valide');
    const traitee = page.locator('tbody tr').filter({ hasText: CAISSE });
    await expect(traitee).toContainText('Décision rendue');
    await expect(traitee).toContainText('Écart mineur, justification acceptée');
  });
});
