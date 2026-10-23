import type { Page } from '@playwright/test';
import { attendreToast, expect, seConnecter, test } from '../support/test';

/**
 * Menu 2 — Finances & Comptabilité › 2.6 Dépenses, et règle transversale
 * « Circuit de validation des dépenses » (§ 6) : saisie par le Comptable,
 * validation ou rejet motivé par la Direction, règlement par une caisse.
 * La ventilation analytique d'une dépense (§ 6) est vérifiée ici aussi.
 */
test.describe.configure({ mode: 'serial' });

async function saisirDepense(page: Page, libelle: string, montant: string): Promise<void> {
  await page.goto('/finances/depenses');
  await page.locator('#btnNouvelleDepense').click();
  const formulaire = page.locator('#depense-form');
  await formulaire.locator('[name=libelle]').fill(libelle);
  await formulaire.locator('[name=beneficiaire]').fill('Quincaillerie Moderne');
  await formulaire.locator('[name=montant]').fill(montant);
  await formulaire.locator('[name=motif_depense]').fill('Entretien des salles de classe');
  await formulaire.locator('[name=justificatif]').setInputFiles({
    name: 'facture.pdf',
    mimeType: 'application/pdf',
    buffer: Buffer.from('%PDF-1.4\n% facture de test\n'),
  });
  await formulaire.locator('button[type=submit]').click();
  await attendreToast(page);
}

const ligneDe = (page: Page, libelle: string) => page.locator('tbody tr').filter({ hasText: libelle });

test.describe('2.6 Dépenses', () => {
  test('une dépense sans libellé ni montant est refusée', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/depenses');
    await page.locator('#btnNouvelleDepense').click();
    await page.locator('#depense-form button[type=submit]').click();

    await expect(page.locator('#depenseModal')).toBeVisible();
    await expect(page.locator('#depenseModal .is-invalid').first()).toBeVisible();
  });

  test('le Comptable saisit une dépense avec justificatif : à partir de 150 000 F, elle attend la validation', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await saisirDepense(page, 'Peinture des salles', '175000');

    const ligne = ligneDe(page, 'Peinture des salles');
    await expect(ligne).toContainText('Quincaillerie Moderne');
    await expect(ligne).toContainText('175 000,00');
    await expect(ligne).toContainText('En attente');

    // Le Comptable saisit mais ne valide pas
    await ligne.locator('.btn-action').click();
    await expect(ligne.locator('.btn-valider')).toHaveCount(0);
    await expect(ligne.getByRole('link', { name: /justificatif/i })).toBeVisible();
  });

  test('la Direction valide la dépense', async ({ page }) => {
    await seConnecter(page, 'directeur');
    await page.goto('/finances/depenses');

    const ligne = ligneDe(page, 'Peinture des salles');
    await ligne.locator('.btn-action').click();
    await ligne.locator('.btn-valider').click();
    await page.locator('#valider-form button[type=submit]').click();
    await attendreToast(page);

    await expect(ligneDe(page, 'Peinture des salles')).toContainText('Validée');
    // La Direction valide mais ne saisit pas
    await expect(page.locator('#btnNouvelleDepense')).toHaveCount(0);
  });

  test('la Direction rejette une dépense en indiquant le motif', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await saisirDepense(page, 'Achat de climatiseur', '450000');

    await seConnecter(page, 'directeur');
    await page.goto('/finances/depenses');
    const ligne = ligneDe(page, 'Achat de climatiseur');
    await ligne.locator('.btn-action').click();
    await ligne.locator('.btn-rejeter').click();
    await page.locator('#rejeter-form [name=motif_rejet]').fill('Hors budget cette année');
    await page.locator('#rejeter-form button[type=submit]').click();
    await attendreToast(page);

    const rejetee = ligneDe(page, 'Achat de climatiseur');
    await expect(rejetee).toContainText('Rejetée');
    await rejetee.locator('.btn-action').click();
    await expect(rejetee).toContainText('Hors budget');

    // Filtre par statut
    const valeur = await page.locator('select[name=statut] option', { hasText: 'Rejetée' }).getAttribute('value');
    await page.locator('select[name=statut]').selectOption(valeur!);
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText('Achat de climatiseur');
    await expect(page.locator('tbody')).not.toContainText('Peinture des salles');
  });

  test('une dépense validée est rattachée à une caisse ouverte pour règlement', async ({ page }) => {
    // Une caisse ouverte par le Caissier (celle d'un autre scénario si elle est encore ouverte)
    await seConnecter(page, 'caissier');
    await page.goto('/finances/caisses');
    if ((await page.locator('tbody tr').filter({ hasText: 'Ouverte' }).count()) === 0) {
      await page.locator('[data-bs-target="#caisseModal"]').click();
      const modale = page.locator('#caisseModal');
      await modale.locator('[name=libelle]').fill('Caisse dépenses');
      await modale.locator('[name=solde_initial]').fill('200000');
      await modale.locator('button[type=submit]').click();
      await attendreToast(page);
    }

    await seConnecter(page, 'comptable');
    await page.goto('/finances/depenses');
    const ligne = ligneDe(page, 'Peinture des salles');
    await ligne.locator('.btn-action').click();
    await ligne.locator('.btn-regler').click();
    await expect(page.locator('#regler-libelle')).toHaveText('Peinture des salles');

    // Seules les caisses ouvertes sont proposées
    const choix = page.locator('#regler-form [name=caisse_id]');
    await choix.selectOption({ index: 1 });
    const nomCaisse = (await choix.locator('option:checked').innerText()).trim();
    await page.locator('#regler-form button[type=submit]').click();
    await attendreToast(page);

    // La dépense porte sa caisse et sa date de règlement, et ne peut plus être réglée une seconde fois
    const reglee = ligneDe(page, 'Peinture des salles');
    await expect(reglee).toContainText(nomCaisse);
    await expect(reglee).toContainText(/le \d{2}\/\d{2}\/\d{4}/);
    await reglee.locator('.btn-action').click();
    await expect(reglee.locator('.btn-regler')).toHaveCount(0);

    // La sortie figure au journal de la caisse (§ 6 « Journal de caisse ») — « Toutes les caisses » : Direction
    await seConnecter(page, 'directeur');
    await page.goto('/finances/caisses');
    const caisse = page.locator('tbody tr').filter({ hasText: nomCaisse }).filter({ hasText: 'Ouverte' });
    await caisse.locator('.btn-action').click();
    await caisse.getByRole('link', { name: /journal/i }).click();
    await expect(page.locator('main')).toContainText('Peinture des salles');
    await expect(page.locator('main')).toContainText('175 000,00');
  });

  test('une dépense de moins de 150 000 F est validée d\'office ; le Caissier la décaisse depuis sa caisse ouverte', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await saisirDepense(page, 'Ampoules du préau', '12000');
    await expect(ligneDe(page, 'Ampoules du préau')).toContainText('Validée');
    await saisirDepense(page, 'Tables de la bibliothèque', '260000');
    await expect(ligneDe(page, 'Tables de la bibliothèque')).toContainText('En attente');

    // Onglet « Dépenses en attente » : validées ou non, pas encore décaissées ; seule la validée se décaisse
    await seConnecter(page, 'caissier');
    await page.goto('/finances/decaissements?onglet=attente');
    const enAttente = page.locator('#table-attente tbody');
    const validee = enAttente.locator('tr').filter({ hasText: 'Ampoules du préau' });
    const nonValidee = enAttente.locator('tr').filter({ hasText: 'Tables de la bibliothèque' });
    await expect(validee).toContainText('Validée');
    await expect(validee.locator('.btn-decaisser')).toBeEnabled();
    await expect(nonValidee).toContainText('En attente de validation');
    await expect(nonValidee.locator('.btn-decaisser')).toBeDisabled();
    await expect(enAttente).not.toContainText('Achat de climatiseur');   // rejetée
    await expect(enAttente).not.toContainText('Peinture des salles');    // déjà décaissée

    // Le Caissier (caisse ouverte par le scénario précédent) : onglet « Caisse ouverte »
    await seConnecter(page, 'caissier');
    await page.goto('/finances/decaissements');
    await expect(page.locator('#onglet-caisse')).toHaveClass(/active/);
    await expect(page.locator('#onglet-tous')).toBeVisible();
    await expect(page.locator('#onglet-mes')).toBeVisible();
    await expect(page.locator('#btn-journal-caisse')).toHaveAttribute('href', /\/finances\/caisses\/\d+$/);

    await page.locator('#btn-nouveau-decaissement').click();
    const aDecaisser = page.locator('#liste-a-decaisser .ligne').filter({ hasText: 'Ampoules du préau' });
    await expect(aDecaisser).toContainText('validée d\'office');
    await expect(page.locator('#liste-a-decaisser')).not.toContainText('Achat de climatiseur');   // rejetée
    await aDecaisser.locator('.btn-decaisser').click();

    // Fenêtre de confirmation : montant réellement décaissé (proposé : celui de la dépense) et caisse utilisée
    const fenetre = page.locator('#decaisserModal');
    await expect(fenetre).toBeVisible();
    await expect(fenetre.locator('#dec-libelle')).toHaveText('Ampoules du préau');
    await expect(fenetre.locator('#dec-montant')).toHaveValue('12000');
    await expect(fenetre.locator('#dec-ma-caisse')).toContainText('Solde théorique');   // ma caisse ouverte, pas de liste
    await expect(fenetre.locator('select[name=caisse_id]')).toHaveCount(0);
    await fenetre.locator('#dec-montant').fill('11500');
    await expect(fenetre.locator('#dec-ecart')).toContainText(/500 F de moins/);
    await fenetre.locator('#dec-valider').click();
    await attendreToast(page, 'Décaissement enregistré');

    // La sortie (montant réel) figure dans la caisse ouverte, dans « Mes décaissements » et au journal
    const sortie = page.locator('#table-decaissements tbody tr').filter({ hasText: 'Ampoules du préau' });
    await expect(sortie).toContainText(/11\s500 F/);
    await expect(sortie).toContainText('Dépense en espèces');
    await page.locator('#onglet-mes').click();
    await expect(page.locator('#table-decaissements tbody')).toContainText('Ampoules du préau');
    await page.locator('#onglet-caisse').click();
    await page.locator('#btn-journal-caisse').click();
    await expect(page.locator('main')).toContainText('Ampoules du préau');
  });

  test('une dépense rejetée ou en attente ne peut pas être réglée', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/depenses');
    const ligne = ligneDe(page, 'Achat de climatiseur');
    await ligne.locator('.btn-action').click();
    await expect(ligne.locator('.btn-regler')).toHaveCount(0);
  });

  test('la ventilation analytique d\'une dépense doit couvrir 100 % de son montant', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/depenses');
    const ligne = ligneDe(page, 'Peinture des salles');
    await ligne.locator('.btn-action').click();
    await ligne.getByRole('link', { name: /Détail|Consulter|Voir la/i }).first().click();
    await expect(page.locator('.page-title-main')).toContainText('Détail de la dépense');

    await page.locator('[data-bs-target="#ventilationModal"]').click();
    const modale = page.locator('#ventilationModal');
    if ((await modale.locator('select[name*="centre_analytique_id"]').count()) === 0) {
      await modale.locator('#add-row').click();
    }
    await modale.locator('select[name*="centre_analytique_id"]').first().selectOption({ index: 1 });

    // Ventilation partielle : refusée
    await modale.locator('input[name*="[montant]"]').first().fill('50000');
    await modale.locator('button[type=submit]').click();
    await expect(page.locator('#toast-container .toast-error').last()).toBeVisible();
    await expect(modale).toBeVisible();

    // Ventilation complète : acceptée
    await modale.locator('input[name*="[montant]"]').first().fill('175000');
    await modale.locator('button[type=submit]').click();
    await attendreToast(page);
    const imputation = page.locator('table tbody tr').filter({ hasText: 'Cantine' });
    await expect(imputation).toContainText('175 000,00');
    await expect(imputation).toContainText('100,00 %');
    await expect(page.locator('table tfoot')).toContainText('100,00 %');
  });

  test('la liste des dépenses s\'exporte en Excel et en PDF', async ({ page }) => {
    await seConnecter(page, 'comptable');
    await page.goto('/finances/depenses');

    const excel = await page.request.get(await page.locator('a[href*="/export"]').first().getAttribute('href').then((h) => h!));
    expect(excel.status()).toBe(200);
    const pdf = await page.request.get(await page.locator('a[href*="export-pdf"], a[href*="exportPdf"]').first().getAttribute('href').then((h) => h!));
    expect(pdf.status()).toBe(200);
  });
});
