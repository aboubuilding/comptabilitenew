import { COMPTES, MOT_DE_PASSE } from '../support/comptes';
import { menuDe } from '../support/menu';
import { attendreToast, expect, seConnecter, test } from '../support/test';

/**
 * Écrans communs à tous les profils : tableau de bord, « Mon profil »
 * (changement de mot de passe) et recherche globale.
 */
test.describe('Tableau de bord', () => {
  test('chaque profil y trouve ses accès rapides, et aucun lien vers un écran interdit', async ({ page }) => {
    for (const profil of ['comptable', 'caissier', 'secretaire', 'transport'] as const) {
      await seConnecter(page, profil);
      await page.goto('/tableau');

      const attendus = menuDe(profil).filter((e) => e.autorise).map((e) => e.chemin);
      const liens = await page
        .locator('#acces-rapides a.acces-rapide')
        .evaluateAll((as) => as.map((a) => new URL((a as HTMLAnchorElement).href).pathname));

      expect(liens.length, `accès rapides de ${profil}`).toBeGreaterThan(0);
      for (const lien of liens) {
        expect(attendus, `${profil} → ${lien}`).toContain(lien);
      }

      // Les chiffres d'administration (comptes, journal) ne concernent que l'Administrateur
      await expect(page.locator('#dernieres-activites')).toHaveCount(0);
      await expect(page.locator('.stat-label', { hasText: 'Utilisateurs' })).toHaveCount(0);
    }
  });

  test('l\'Administrateur voit les statistiques des comptes et les dernières activités du journal', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/tableau');

    await expect(page.locator('.stat-label', { hasText: 'Utilisateurs' })).toBeVisible();
    await expect(page.locator('#dernieres-activites')).toBeVisible();

    await page.locator('#dernieres-activites').getByRole('link', { name: /Voir tout/ }).click();
    await expect(page).toHaveURL(/\/administration\/journal/);
  });

  test('un profil sans module (Enseignant) est informé qu\'aucun accès ne lui est attribué', async ({ page }) => {
    await seConnecter(page, 'enseignant');
    await page.goto('/tableau');

    await expect(page.locator('#aucun-acces')).toContainText('Aucun module ne vous est attribué');
    await expect(page.locator('#acces-rapides')).toHaveCount(0);
  });

  test('aucun lien du tableau de bord ne mène à une page introuvable', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/tableau');

    const liens = await page
      .locator('.page-wrapper a[href]')
      .evaluateAll((as) => [...new Set(as.map((a) => (a as HTMLAnchorElement).href))].filter((h) => h.startsWith(location.origin) && !h.endsWith('#')));

    for (const lien of liens) {
      const reponse = await page.request.get(lien);
      expect(reponse.status(), lien).toBe(200);
    }
  });
});

test.describe('Mon profil', () => {
  test('le menu utilisateur mène au profil, qui affiche le compte et ses profils', async ({ page }) => {
    await seConnecter(page, 'biblio');
    await page.goto('/tableau');

    await page.locator('#hbtp-avatar-btn').click();
    await page.getByRole('menuitem', { name: 'Mon profil' }).click();

    await expect(page).toHaveURL(/\/profil$/);
    await expect(page.locator('main')).toContainText(COMPTES.biblio);
    await expect(page.locator('main')).toContainText('Bibliothécaire');
  });

  test('un utilisateur change son mot de passe, puis se connecte avec le nouveau', async ({ page }) => {
    const nouveau = 'Nouveau-Secret-2';

    await seConnecter(page, 'activites');
    await page.goto('/profil');

    // Mot de passe actuel erroné : refusé
    await page.locator('#mot_passe_actuel').fill('pas-le-bon');
    await page.locator('#mot_passe').fill(nouveau);
    await page.locator('#mot_passe_confirmation').fill(nouveau);
    await page.locator('#form-mot-de-passe button[type=submit]').click();
    await expect(page.locator('#mot_passe_actuel')).toHaveClass(/is-invalid/);
    await expect(page.locator('.invalid-feedback').first()).toContainText('incorrect');

    // Confirmation différente : refusée
    await page.locator('#mot_passe_actuel').fill(MOT_DE_PASSE);
    await page.locator('#mot_passe').fill(nouveau);
    await page.locator('#mot_passe_confirmation').fill('autre-chose-9');
    await page.locator('#form-mot-de-passe button[type=submit]').click();
    await expect(page.locator('#mot_passe')).toHaveClass(/is-invalid/);

    // Changement effectif
    await page.locator('#mot_passe_actuel').fill(MOT_DE_PASSE);
    await page.locator('#mot_passe').fill(nouveau);
    await page.locator('#mot_passe_confirmation').fill(nouveau);
    await page.locator('#form-mot-de-passe button[type=submit]').click();
    await attendreToast(page, 'mot de passe');

    // L'ancien mot de passe ne fonctionne plus, le nouveau si
    await page.context().clearCookies();
    await page.goto('/login');
    await page.locator('#login').fill(COMPTES.activites);
    await page.locator('#mot_passe').fill(MOT_DE_PASSE);
    await page.locator('#btn-login').click();
    await expect(page.locator('#alert-banner-title')).toHaveText('Mot de passe incorrect');

    await page.locator('#mot_passe').fill(nouveau);
    await page.locator('#btn-login').click();
    await expect(page).toHaveURL(/\/tableau$/);

    // Remise en état pour les autres scénarios
    await page.goto('/profil');
    await page.locator('#mot_passe_actuel').fill(nouveau);
    await page.locator('#mot_passe').fill(MOT_DE_PASSE);
    await page.locator('#mot_passe_confirmation').fill(MOT_DE_PASSE);
    await page.locator('#form-mot-de-passe button[type=submit]').click();
    await attendreToast(page, 'mot de passe');
  });
});

test.describe('Recherche globale', () => {
  test('la recherche trouve un élève et un écran, et ouvre le résultat choisi', async ({ page }) => {
    await seConnecter(page, 'secretaire');
    await page.goto('/tableau');

    await page.locator('#btnSearch').click();
    await expect(page.locator('#searchModal')).toHaveClass(/open/);

    await page.locator('#searchInput').fill('koffi');
    const resultats = page.locator('#searchResults .search-result-item');
    await expect(resultats.filter({ hasText: 'KOFFI Ama' })).toBeVisible();
    await expect(resultats.filter({ hasText: 'Famille KOFFI' })).toBeVisible();

    await resultats.filter({ hasText: 'KOFFI Ama' }).click();
    await expect(page).toHaveURL(/\/scolarite\/eleves\/\d+$/);
    await expect(page.locator('.page-title-main')).toContainText('KOFFI');
  });

  test('la recherche ne révèle rien de plus que les droits du profil', async ({ page }) => {
    // Le Caissier n'a accès ni aux élèves ni aux inscriptions
    await seConnecter(page, 'caissier');
    const eleves = await (await page.request.get('/recherche?q=koffi')).json();
    expect(eleves).toEqual([]);

    const ecrans = await (await page.request.get('/recherche?q=caisse')).json();
    expect(ecrans.map((r: { title: string }) => r.title)).toContain('Caisses');
    expect(ecrans.map((r: { title: string }) => r.title)).not.toContain('Écarts de caisse à valider');

    await page.goto('/tableau');
    await page.keyboard.press('Control+k');
    await page.locator('#searchInput').fill('zzzzzz');
    await expect(page.locator('#searchResults')).toContainText('Aucun résultat');
  });
});
