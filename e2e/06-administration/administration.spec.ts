import type { Page } from '@playwright/test';
import { attendreToast, confirmer, expect, seConnecter, test } from '../support/test';

/**
 * Menu 6 — Administration & Communications :
 *   6.1 Utilisateurs & Profils (Administrateur) — dont le cumul de profils (§ 3)
 *   6.3 Communications (Secrétariat, Direction, Comptable / Recouvrement ; modèles : Administrateur)
 *   6.4 Journal & Traçabilité (Administrateur)
 */
test.describe.configure({ mode: 'serial' });

const ligneDe = (page: Page, texte: string | RegExp) => page.locator('tbody tr').filter({ hasText: texte });
const MOT_DE_PASSE = 'Cumul-Secret-1';

async function seConnecterParFormulaire(page: Page, login: string, motDePasse: string): Promise<void> {
  await page.context().clearCookies();
  await page.goto('/login');
  await page.locator('#login').fill(login);
  await page.locator('#mot_passe').fill(motDePasse);
  await page.locator('#btn-login').click();
}

test.describe('6.1 Utilisateurs & Profils', () => {
  test('la liste affiche le nom, le login, le profil, le statut et la dernière connexion', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/administration/users');

    for (const colonne of ['Nom', 'Login', 'Profil', 'Statut', 'Dernière connexion']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }
    const admin = ligneDe(page, 'e2e.admin');
    await expect(admin).toContainText('Administrateur');
    await expect(admin).toContainText('Actif');
    await expect(admin).toContainText('127.0.0.1');
  });

  test('créer un utilisateur cumulant deux profils : Comptable et Caissier', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/administration/users');

    await page.locator('#btn-nouvel-user').click();
    const formulaire = page.locator('#formUser');
    await formulaire.locator('[name=nom]').fill('ATTIOGBE');
    await formulaire.locator('[name=prenom]').fill('Essi');
    await formulaire.locator('[name=login]').fill('essi.attiogbe');
    await formulaire.locator('[name=email]').fill('essi.attiogbe@ecole.test');
    await formulaire.locator('[name=mot_passe]').fill(MOT_DE_PASSE);
    await formulaire.locator('[name=role_id]').selectOption({ label: 'Comptable' });
    await formulaire.locator('.form-check').filter({ hasText: /Caissier/ }).locator('input').check();
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    const utilisateur = ligneDe(page, 'essi.attiogbe');
    await expect(utilisateur).toContainText('ATTIOGBE');
    await expect(utilisateur).toContainText('Comptable');
    await expect(utilisateur).toContainText(/\+ Caissier/);
    await expect(utilisateur).toContainText('Actif');
  });

  test('le cumul donne les droits des deux profils : saisir un paiement ET encaisser', async ({ page }) => {
    await seConnecterParFormulaire(page, 'essi.attiogbe', MOT_DE_PASSE);
    await expect(page).toHaveURL(/\/tableau$/);
    await expect(page.locator('.hbtp-avatar-role').first()).toHaveText('Comptable');

    const liens = await page
      .locator('#hbtp-nav-items > .hnav-item:not(.hnav-more) .hnav-drop-item')
      .evaluateAll((as) => as.map((a) => new URL((a as HTMLAnchorElement).href).pathname));
    expect(liens).toContain('/finances/paiements');       // droit du Comptable
    expect(liens).toContain('/finances/encaissements');   // droit du Caissier
    expect(liens).not.toContain('/administration/users'); // aucun des deux ne gère les comptes

    for (const chemin of ['/finances/paiements', '/finances/encaissements']) {
      expect((await page.request.get(chemin)).status(), chemin).toBe(200);
    }
    expect((await page.request.get('/administration/users', { maxRedirects: 0 })).status()).toBe(403);

    // Son profil rappelle ses deux profils
    await page.goto('/profil');
    await expect(page.locator('main')).toContainText('Comptable');
    await expect(page.locator('main')).toContainText(/Caissier/);
  });

  test('un login déjà pris et un formulaire incomplet sont refusés', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/administration/users');

    await page.locator('#btn-nouvel-user').click();
    const formulaire = page.locator('#formUser');
    await formulaire.locator('[name=nom]').fill('DOUBLON');
    await formulaire.locator('[name=prenom]').fill('Test');
    await formulaire.locator('[name=login]').fill('essi.attiogbe');
    await formulaire.locator('[name=mot_passe]').fill(MOT_DE_PASSE);
    await formulaire.locator('[name=role_id]').selectOption({ label: 'Secrétaire' });
    await formulaire.locator('button[type=submit]').click();

    await expect(formulaire.locator('[name=login]')).toHaveClass(/is-invalid/);
    await expect(page.locator('#modalUser')).toBeVisible();
    await page.reload();
    await expect(page.locator('tbody')).not.toContainText('DOUBLON');
  });

  test('modifier un utilisateur : changer de profil sans retaper le mot de passe', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/administration/users');

    const utilisateur = ligneDe(page, 'essi.attiogbe');
    await utilisateur.locator('.btn-action').click();
    await utilisateur.locator('.btn-edit-user').click();

    const formulaire = page.locator('#formUser');
    await expect(formulaire.locator('[name=login]')).toHaveValue('essi.attiogbe');
    await expect(formulaire.locator('.form-check').filter({ hasText: /Caissier/ }).locator('input')).toBeChecked();
    await formulaire.locator('.form-check').filter({ hasText: /Caissier/ }).locator('input').uncheck();
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    await expect(ligneDe(page, 'essi.attiogbe')).not.toContainText('+ Caissier');

    // Le mot de passe est conservé, et le droit d'encaisser a disparu
    await seConnecterParFormulaire(page, 'essi.attiogbe', MOT_DE_PASSE);
    await expect(page).toHaveURL(/\/tableau$/);
    expect((await page.request.get('/finances/encaissements', { maxRedirects: 0 })).status()).toBe(403);
  });

  test('désactiver un compte l\'empêche de se connecter', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/administration/users');

    const utilisateur = ligneDe(page, 'essi.attiogbe');
    await utilisateur.locator('.btn-action').click();
    await utilisateur.locator('form button[type=submit]').click();
    await confirmer(page);
    await attendreToast(page);

    await seConnecterParFormulaire(page, 'essi.attiogbe', MOT_DE_PASSE);
    await expect(page.locator('.swal2-popup, #alert-banner.show').first()).toBeVisible();
    await expect(page).toHaveURL(/\/login$/);
  });

  test('la liste des utilisateurs s\'exporte ; seul l\'Administrateur y accède', async ({ page }) => {
    await seConnecter(page, 'admin');
    expect((await page.request.get('/administration/users/export')).status()).toBe(200);

    await seConnecter(page, 'directeur');
    expect((await page.request.get('/administration/users', { maxRedirects: 0 })).status()).toBe(403);
  });
});

test.describe('6.3 Communications', () => {
  test('l\'Administrateur crée un modèle de message', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/administration/modeles');

    await page.locator('#btn-nouveau-modele').click();
    const formulaire = page.locator('#formModele');
    await formulaire.locator('[name=code]').fill('RENTREE');
    await formulaire.locator('[name=libelle]').fill('Information de rentrée');
    await formulaire.locator('[name=type]').fill('information');
    await formulaire.locator('[name=sujet]').fill('Rentrée scolaire 2026-2027');
    await formulaire.locator('[name=corps]').fill('Chers parents, la rentrée est fixée au 15 septembre 2026.');
    await formulaire.locator('button[type=submit]').click();
    await attendreToast(page);

    const modele = ligneDe(page, 'RENTREE');
    await expect(modele).toContainText('Information de rentrée');
    await expect(modele).toContainText('Rentrée scolaire 2026-2027');
  });

  test('le secrétariat rédige un message à partir du modèle, en brouillon, puis l\'envoie', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/administration/messages');

    for (const colonne of ['Sujet', 'Modèle', 'Expéditeur', 'Destinataires', 'Statut', 'Date d\'envoi']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    await page.locator('#btn-nouveau-message').click();
    const formulaire = page.locator('#formMessage');
    await formulaire.locator('#select-modele').selectOption({ label: 'Information de rentrée' });
    // Le modèle pré-remplit le sujet et le corps
    await expect(formulaire.locator('[name=sujet]')).toHaveValue('Rentrée scolaire 2026-2027');
    await expect(formulaire.locator('[name=corps]')).toHaveValue(/la rentrée est fixée/);

    await formulaire.locator('#ciblage_type').selectOption('libre');
    await formulaire.locator('[name=emails_libres_text]').fill('parent.un@exemple.test\nparent.deux@exemple.test');
    await formulaire.getByRole('button', { name: /brouillon/i }).click();
    await attendreToast(page);

    let message = ligneDe(page, 'Rentrée scolaire 2026-2027');
    await expect(message).toContainText('Information de rentrée');
    await expect(message).toContainText('Brouillon');
    await expect(message.locator('.badge.bg-secondary')).toHaveText('2');

    // Envoi du brouillon
    await message.locator('.btn-action').click();
    await message.locator('form[action*="/envoyer"] button').click();
    const confirmation = page.locator('.swal2-confirm');
    if (await confirmation.isVisible().catch(() => false)) await confirmation.click();
    await attendreToast(page);

    message = ligneDe(page, 'Rentrée scolaire 2026-2027');
    await expect(message).toContainText('Envoyé');
    await expect(message.locator('.badge.bg-success')).toHaveText('2');

    // Statut de livraison par destinataire
    await message.locator('.btn-action').click();
    await message.locator('a.dropdown-item').first().click();
    await expect(ligneDe(page, 'parent.un@exemple.test')).toContainText(/envoy/i);
    await expect(ligneDe(page, 'parent.deux@exemple.test')).toContainText(/envoy/i);
  });

  test('message groupé par classe : les parents des élèves de la classe sont ciblés', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/administration/messages');

    await page.locator('#btn-nouveau-message').click();
    const formulaire = page.locator('#formMessage');
    await formulaire.locator('[name=sujet]').fill('Réunion des parents de CP A');
    await formulaire.locator('[name=corps]').fill('Réunion le vendredi 9 octobre à 17 h.');

    // Le choix du ciblage affiche le champ correspondant, avec la liste des classes
    await formulaire.locator('#ciblage_type').selectOption('classe');
    await expect(formulaire.locator('[name=classe_id]')).toBeVisible();
    await expect(formulaire.locator('[name=emails_libres_text]')).toBeHidden();
    await formulaire.locator('[name=classe_id]').selectOption({ label: 'CP A' });
    await formulaire.getByRole('button', { name: /Enregistrer et envoyer/ }).click();
    await attendreToast(page);

    // Une famille en CP A (deux enfants, un parent avec email) → un destinataire
    const message = ligneDe(page, 'Réunion des parents de CP A');
    await expect(message).toContainText('Envoyé');
    await message.locator('.btn-action').click();
    await message.locator('a.dropdown-item').first().click();
    await expect(ligneDe(page, 'yao.koffi@exemple.test')).toHaveCount(1);
  });

  test('un message sans destinataire valide est refusé', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/administration/messages');

    await page.locator('#btn-nouveau-message').click();
    const formulaire = page.locator('#formMessage');
    await formulaire.locator('[name=sujet]').fill('Message sans destinataire');
    await formulaire.locator('[name=corps]').fill('Texte');
    await formulaire.locator('#ciblage_type').selectOption('libre');
    await formulaire.getByRole('button', { name: /brouillon/i }).click();

    await expect(page.locator('#toast-container .toast-error, #formMessage .is-invalid').first()).toBeVisible();
    await page.reload();
    await expect(page.locator('tbody')).not.toContainText('Message sans destinataire');
  });

  test('l\'historique des envois et l\'export ; les modèles restent réservés à l\'Administrateur', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/administration/messages/historique');
    await expect(page.locator('main.content-area')).toBeVisible();

    expect((await page.request.get('/administration/messages/export')).status()).toBe(200);
    expect((await page.request.get('/administration/modeles', { maxRedirects: 0 })).status()).toBe(403);
  });

  test('la Direction et le Chargé de recouvrement peuvent communiquer ; le Caissier non', async ({ page }) => {
    for (const profil of ['directeur', 'recouvrement'] as const) {
      await seConnecter(page, profil);
      await page.goto('/administration/messages');
      await expect(page.locator('#btn-nouveau-message')).toBeVisible();
    }

    await seConnecter(page, 'caissier');
    expect((await page.request.get('/administration/messages', { maxRedirects: 0 })).status()).toBe(403);
  });
});

test.describe('6.4 Journal & Traçabilité', () => {
  test('le journal retrace qui a fait quoi, quand, depuis quelle adresse et dans quel module', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/administration/journal');

    for (const colonne of ['Date', 'Utilisateur', 'Module', 'Action', 'Description', 'IP']) {
      await expect(page.locator('thead th').filter({ hasText: colonne }).first()).toBeVisible();
    }

    // La création du message par le secrétariat a été journalisée
    const action = ligneDe(page, 'Rentrée scolaire 2026-2027').first();
    await expect(action).toContainText('e2e.secretaire');
    await expect(action).toContainText('127.0.0.1');

    // Filtre par recherche
    await page.locator('form[method=GET] input[name=recherche]').fill('essi.attiogbe');
    await page.locator('form[method=GET] button[type=submit]').click();
    await expect(page.locator('tbody')).toContainText(/ATTIOGBE|essi\.attiogbe/);
    await expect(page.locator('tbody')).not.toContainText('Rentrée scolaire');
  });

  test('le journal s\'exporte ; lui seul, l\'Administrateur, y accède', async ({ page }) => {
    await seConnecter(page, 'admin');
    const export_ = await page.request.get('/administration/journal/export?recherche=essi.attiogbe');
    expect(export_.status()).toBe(200);

    for (const profil of ['directeur', 'adjoint'] as const) {
      await seConnecter(page, profil);
      expect((await page.request.get('/administration/journal', { maxRedirects: 0 })).status(), profil).toBe(403);
    }
  });
});
