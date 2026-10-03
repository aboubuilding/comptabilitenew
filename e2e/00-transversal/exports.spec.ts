import { menuDe } from '../support/menu';
import { expect, seConnecter, test } from '../support/test';

/**
 * Règle transversale « Export des données » (§ 6) : les écrans de liste
 * proposent un export au format Excel (.xlsx). On parcourt tous les écrans du
 * menu et on télécharge chaque export proposé.
 */
test('chaque export de liste produit un classeur Excel (.xlsx)', async ({ page }) => {
  test.setTimeout(600_000);
  await seConnecter(page, 'admin');

  const exports = new Map<string, string>();

  for (const entree of menuDe('admin').filter((e) => e.autorise)) {
    await page.goto(entree.chemin, { waitUntil: 'domcontentloaded' });
    const liens = await page
      .locator('.page-wrapper a[href*="/export"]')
      .evaluateAll((as) => as.map((a) => (a as HTMLAnchorElement).href));

    for (const lien of liens) {
      // Les éditions PDF (rapports imprimables) ne sont pas des exports de liste
      if (/pdf/i.test(lien)) continue;
      exports.set(new URL(lien).pathname, entree.libelle);
    }
  }

  expect(exports.size, 'nombre d\'écrans proposant un export').toBeGreaterThan(20);

  const ecarts: string[] = [];
  for (const [chemin, ecran] of exports) {
    const reponse = await page.request.get(chemin);
    const type = reponse.headers()['content-type'] ?? '';
    const fichier = reponse.headers()['content-disposition'] ?? '';
    const corps = await reponse.body();

    const estClasseur = reponse.status() === 200
      && type.includes('spreadsheetml')
      && /\.xlsx/.test(fichier)
      && corps.subarray(0, 2).toString() === 'PK'; // un .xlsx est une archive zip

    if (!estClasseur) ecarts.push(`${ecran} — ${chemin} : HTTP ${reponse.status()}, ${type || 'type inconnu'}, ${fichier}`);
  }

  expect(ecarts, 'Exports qui ne sont pas des classeurs .xlsx').toEqual([]);
});
