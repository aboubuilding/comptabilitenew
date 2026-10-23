import type { Page } from '@playwright/test';
import { COMPTES, type Profil } from '../support/comptes';
import { MENUS, menuDe } from '../support/menu';
import { expect, seConnecter, test } from '../support/test';

/**
 * Matrice des droits — cahier des charges § 3, § 4 (« Profils concernés » de
 * chaque sous-menu) et § 5. Pour chaque profil, on vérifie dans le navigateur :
 *   - que le menu affiche exactement les écrans auxquels il a droit ;
 *   - que chacun de ces écrans s'ouvre sans erreur (serveur ou JavaScript) ;
 *   - que les autres écrans sont refusés (403), même en tapant l'adresse.
 */
const PROFILS = Object.keys(COMPTES) as Profil[];

/**
 * Défauts de présentation qu'aucun contrôle côté serveur ne voit : bouton
 * resté à l'apparence brute du navigateur (classe sans style), séparateur de
 * fil d'Ariane remplacé par une URL, texte de débogage laissé à l'écran.
 */
async function defautsDePresentation(page: Page): Promise<string[]> {
  return page.evaluate(() => {
    const defauts: string[] = [];
    const visible = (el: Element) => (el as HTMLElement).offsetParent !== null;

    document.querySelectorAll('.page-wrapper button, .page-wrapper a[class*="btn"]').forEach((el) => {
      if (!visible(el)) return;
      const style = getComputedStyle(el);
      const brut = el.tagName === 'BUTTON' ? style.borderTopStyle === 'outset' : style.textDecorationLine.includes('underline');
      if (brut) defauts.push(`bouton sans style : « ${(el.textContent ?? '').trim().slice(0, 40)} » (${el.className})`);
    });

    document.querySelectorAll('.breadcrumb-custom li').forEach((el) => {
      const separateur = getComputedStyle(el, '::before').content;
      if (/https?:/.test(separateur)) defauts.push(`fil d'Ariane : séparateur ${separateur}`);
    });

    const texte = document.querySelector('.page-wrapper')?.textContent ?? '';
    if (/Année ID\s*:/.test(texte)) defauts.push('texte de débogage « Année ID »');

    return defauts;
  });
}

for (const profil of PROFILS) {
  test.describe(`Profil ${profil}`, () => {
    test.beforeEach(async ({ page }) => {
      await seConnecter(page, profil);
    });

    test('le menu n\'affiche que les écrans autorisés', async ({ page }) => {
      const attendu = menuDe(profil);
      await page.goto('/tableau');

      const liens = await page
        .locator('#hbtp-nav-items > .hnav-item:not(.hnav-more) .hnav-drop-item')
        .evaluateAll((elements) => elements.map((a) => new URL((a as HTMLAnchorElement).href).pathname));

      expect(liens.sort()).toEqual(attendu.filter((e) => e.autorise).map((e) => e.chemin).sort());

      for (const menu of MENUS) {
        const visible = attendu.some((e) => e.menu === menu && e.autorise);
        const titres = page.locator('#hbtp-nav-items > .hnav-item:not(.hnav-more) > .hnav-trigger').filter({ hasText: menu });
        await expect(titres, `menu « ${menu} »`).toHaveCount(visible ? 1 : 0);
      }
    });

    test('chaque écran autorisé s\'ouvre sans erreur', async ({ page }) => {
      test.setTimeout(240_000);

      for (const entree of menuDe(profil).filter((e) => e.autorise)) {
        await test.step(`${entree.menu} › ${entree.libelle}`, async () => {
          const reponse = await page.goto(entree.chemin, { waitUntil: 'load' });
          expect(reponse?.status(), entree.chemin).toBe(200);
          await expect(page.locator('main.content-area')).toBeVisible();
          expect(await defautsDePresentation(page), `présentation de ${entree.chemin}`).toEqual([]);
        });
      }
    });

    test('les écrans non autorisés sont refusés, même par adresse directe', async ({ page }) => {
      test.setTimeout(240_000);

      for (const entree of menuDe(profil).filter((e) => !e.autorise)) {
        await test.step(`${entree.menu} › ${entree.libelle}`, async () => {
          const reponse = await page.request.get(entree.chemin, { maxRedirects: 0 });
          expect(reponse.status(), entree.chemin).toBe(403);
        });
      }
    });
  });
}

test.describe('Navigation dans le menu', () => {
  test('un clic dans le menu déroulant ouvre l\'écran correspondant', async ({ page }) => {
    await seConnecter(page, 'admin');
    await page.goto('/tableau');

    const menu = page.locator('#hbtp-nav-items > .hnav-item').filter({ hasText: 'Scolarité' }).first();
    await menu.hover();
    const lien = menu.locator('.hnav-drop-item', { hasText: 'Élèves' });
    // Menu long : le groupe ouvre ses écrans dans un second niveau
    const groupe = menu.locator('.hnav-sub').filter({ has: page.locator('.hnav-drop-item', { hasText: 'Élèves' }) });
    if (await groupe.count()) await groupe.hover();
    await lien.click();

    await expect(page).toHaveURL(/\/scolarite\/eleves$/);
    await expect(page.locator('.page-title-main')).toBeVisible();
  });
});
