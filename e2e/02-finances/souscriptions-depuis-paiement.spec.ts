import type { Locator, Page } from '@playwright/test';
import { attendreToast, expect, ouvrirSaisiePaiement, seConnecter, test } from '../support/test';

/**
 * Menu 2 › 2.2 Paiements — souscrire sans quitter l'écran de saisie.
 *
 * Depuis « Nouveau paiement », le Comptable souscrit l'élève à la cantine, au
 * transport, à une activité ou à un événement dans une fenêtre. Dès la
 * souscription enregistrée, l'engagement (le montant dû) apparaît dans la
 * liste des frais et peut être réglé — en totalité ou en partie — dans le même
 * paiement. Les montants déjà saisis sont conservés.
 *
 * L'élève SAMLAN Yao (E2E0004) ne doit au départ que sa scolarité.
 */
test.describe.configure({ mode: 'serial' });

const MATRICULE = 'E2E0004';
const frais = (page: Page, texte: string | RegExp) => page.locator('#engagements-container .engagement-row').filter({ hasText: texte });

/** Choisit dans une liste l'option dont le libellé contient le texte donné. */
async function choisir(liste: Locator, texte: string): Promise<void> {
  const valeur = await liste.locator('option', { hasText: texte }).first().getAttribute('value');
  await liste.selectOption(valeur!);
}

async function soumettreFenetre(page: Page, fenetre: string, message: string | RegExp): Promise<void> {
  await page.locator(`${fenetre} button[type=submit]`).click();
  await attendreToast(page, message);
  await expect(page.locator(fenetre)).toBeHidden();
}

test.describe('2.2 Nouveau paiement — souscriptions depuis l\'écran', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecter(page, 'comptable');
  });

  test('souscrire cantine, transport, activité et événement puis régler le tout dans le même paiement', async ({ page }) => {
    await ouvrirSaisiePaiement(page, MATRICULE);
    await expect(page.locator('#eleve-bandeau')).toContainText('SAMLAN Yao');
    await expect(page.locator('#total-du')).toHaveText('300 000');

    // Les quatre souscriptions sont proposées
    const barre = page.locator('#barre-souscrire');
    for (const service of ['Cantine', 'Transport', 'Activité', 'Événement']) {
      await expect(barre.getByRole('button', { name: service })).toBeVisible();
    }

    // Un acompte de scolarité, saisi avant les souscriptions, doit être conservé
    await frais(page, 'Frais de scolarité').locator('.engagement-saisie').fill('100000');

    /* ---- Cantine : depuis la ligne « aucun engagement souscrit » ---- */
    await frais(page, 'Cantine').filter({ hasText: 'Aucun engagement souscrit' }).getByRole('button', { name: /Souscrire/ }).click();
    const cantine = page.locator('#modal-cantine');
    await expect(cantine).toBeVisible();
    await expect(cantine.locator('[name=montant_mensuel]')).toHaveValue('15000');   // formule de l'année proposée
    await cantine.locator('[name=nombre_mois]').fill('10');
    await expect(cantine.locator('.du-calcule')).toHaveText(/150\s000/);
    await soumettreFenetre(page, '#modal-cantine', 'cantine');

    // L'engagement est là, mis en évidence, prêt à être réglé ; l'acompte de scolarité est conservé
    const ligneCantine = page.locator('.engagement-row.exists').filter({ hasText: 'Abonnement cantine' });
    await expect(ligneCantine).toHaveClass(/nouveau/);
    await expect(ligneCantine).toContainText(/150\s000,00/);
    await expect(frais(page, 'Frais de scolarité').locator('.engagement-saisie')).toHaveValue('100000');
    await expect(page.locator('#total-du')).toHaveText('450 000');
    await expect(page.locator('#carte-situation')).toContainText('Abonnement cantine');
    await expect(frais(page, 'Aucun engagement souscrit').filter({ hasText: 'Cantine' })).toHaveCount(0);

    // Je règle une partie de la cantine
    await ligneCantine.locator('.engagement-saisie').fill('45000');

    /* ---- Transport : la zone propose son tarif ---- */
    await barre.getByRole('button', { name: 'Transport' }).click();
    const transport = page.locator('#modal-transport');
    await choisir(transport.locator('[name=zone_id]'), 'Zone Kégué');
    await expect(transport.locator('[name=montant_mensuel]')).toHaveValue('12000');
    await transport.locator('[name=nombre_mois]').fill('10');
    await transport.locator('[name=point_arret]').fill('Carrefour Bè-Château');
    await expect(transport.locator('.du-calcule')).toHaveText(/120\s000/);
    await soumettreFenetre(page, '#modal-transport', 'Abonnement bus');

    const ligneBus = page.locator('.engagement-row.exists').filter({ hasText: 'Abonnement bus' });
    await expect(ligneBus).toContainText(/120\s000,00/);
    await expect(ligneCantine.locator('.engagement-saisie')).toHaveValue('45000');

    /* ---- Activité ---- */
    await barre.getByRole('button', { name: 'Activité' }).click();
    const activite = page.locator('#modal-activite');
    await choisir(activite.locator('[name=activite_id]'), 'Taekwondo');
    await expect(activite.locator('[name=montant_du]')).toHaveValue('30000');
    await soumettreFenetre(page, '#modal-activite', 'activité');
    const ligneJudo = page.locator('.engagement-row.exists').filter({ hasText: 'Activité : Taekwondo' });
    await expect(ligneJudo).toContainText(/30\s000,00/);
    await ligneJudo.click();   // un clic solde l'activité
    await expect(ligneJudo.locator('.engagement-saisie')).toHaveValue('30000');

    // Le Taekwondo souscrit n'est plus proposé
    await barre.getByRole('button', { name: 'Activité' }).click();
    await expect(activite.locator('[name=activite_id] option', { hasText: 'Taekwondo' })).toHaveCount(0);
    await activite.getByRole('button', { name: 'Annuler' }).click();
    await expect(activite).toBeHidden();

    /* ---- Événement à places limitées : pré-inscription, puis engagement ---- */
    await barre.getByRole('button', { name: 'Événement' }).click();
    const evenement = page.locator('#modal-evenement');
    await choisir(evenement.locator('[name=evenement_id]'), 'Visite du musée');
    await expect(evenement.locator('#evenement-info')).toContainText(/8\s000/);
    await expect(evenement.locator('#evenement-mode')).toContainText('pré-inscrit');
    await soumettreFenetre(page, '#modal-evenement', 'pré-inscrit');
    const ligneZoo = page.locator('.engagement-row.exists').filter({ hasText: 'Pré-inscription : Visite du musée' });
    await expect(ligneZoo).toContainText(/8\s000,00/);
    await ligneZoo.click();

    /* ---- Événement sans limite de places : la participation va directement au récapitulatif ---- */
    await barre.getByRole('button', { name: 'Événement' }).click();
    await choisir(evenement.locator('[name=evenement_id]'), 'Fête de l');
    await expect(evenement.locator('#evenement-mode')).toContainText('récapitulatif');
    await soumettreFenetre(page, '#modal-evenement', 'récapitulatif');

    /* ---- Récapitulatif : 100 000 + 45 000 + 30 000 + 8 000 + 2 500 = 185 500 ---- */
    await expect(page.locator('#panier-body tr')).toHaveCount(5);
    await expect(page.locator('#panier-body')).toContainText('Fête de l');
    await expect(page.locator('#total-montant')).toHaveText(/185\s500,00/);

    await page.locator('#payeur').fill('SAMLAN Kofi');
    await page.locator('#mode_paiement').selectOption({ label: 'Espèces' });
    await page.locator('#btn-submit').click();
    await expect(page).toHaveURL(/\/finances\/paiements$/);
    await attendreToast(page, 'Paiement enregistré');

    const paiement = page.locator('tbody tr').filter({ hasText: 'SAMLAN Kofi' }).first();
    await expect(paiement).toContainText(/185\s500,00/);
    await expect(paiement).toContainText('5 ligne(s)');
  });

  test('de retour sur l\'élève, la situation compte les souscriptions et ce qui a été saisi', async ({ page }) => {
    await ouvrirSaisiePaiement(page, MATRICULE);

    // Dû : 300 000 + 150 000 + 120 000 + 30 000 + 8 000 = 608 000 ; saisi en attente : 183 000 (la fête n'est pas un engagement)
    await expect(page.locator('#total-du')).toHaveText('608 000');
    await expect(page.locator('#total-attente')).toHaveText('183 000');
    await expect(page.locator('.engagement-row.exists').filter({ hasText: 'Abonnement cantine' })).toContainText(/105\s000,00/);
    await expect(page.locator('.engagement-row.attente').filter({ hasText: 'Activité : Taekwondo' })).toBeVisible();

    // Le service a bien enregistré les souscriptions
    await seConnecter(page, 'admin');
    await page.goto('/services-eleves/cantine/inscriptions');
    await expect(page.locator('tbody tr').filter({ hasText: 'SAMLAN' }).first()).toBeVisible();
  });
});
