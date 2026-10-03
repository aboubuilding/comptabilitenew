import { menuDe } from '../support/menu';
import { expect, seConnecter, test } from '../support/test';

/**
 * Cahier des charges § 4 : chaque sous-menu distingue les profils qui gèrent
 * de ceux qui ne font que consulter. Un profil en consultation ne doit se voir
 * proposer aucune action de création, de modification ou de suppression —
 * pas seulement se la voir refuser au moment de cliquer.
 *
 * L'Administrateur adjoint (consultation de tous les modules) sert de témoin :
 * aucun écran ne doit lui présenter de formulaire d'écriture.
 */
test('un profil en consultation ne se voit proposer aucune action d\'écriture', async ({ page }) => {
  test.setTimeout(300_000);
  await seConnecter(page, 'adjoint');

  const ecarts: string[] = [];

  for (const entree of menuDe('adjoint').filter((e) => e.autorise)) {
    await page.goto(entree.chemin, { waitUntil: 'domcontentloaded' });

    const actions = await page.evaluate(() => {
      const zone = document.querySelector('.page-wrapper');
      if (!zone) return [];
      const trouve: string[] = [];
      const libelle = (el: Element) => (el.textContent ?? '').replace(/\s+/g, ' ').trim().slice(0, 40);

      // Formulaires d'écriture (les filtres sont en GET)
      zone.querySelectorAll('form:not([data-lecture-seule])').forEach((formulaire) => {
        const methode = (formulaire.getAttribute('method') ?? 'get').toLowerCase();
        const estAjax = !formulaire.hasAttribute('method') && formulaire.querySelector('input[name=_token]');
        if (methode === 'post' || estAjax) {
          trouve.push(`formulaire « ${libelle(formulaire.querySelector('[type=submit]') ?? formulaire)} »`);
        }
      });

      // Boutons d'action hors formulaire (ouverture d'une fenêtre de saisie)
      zone.querySelectorAll('button.dropdown-item, button.btn-nouveau, button[data-bs-toggle=modal]').forEach((bouton) => {
        if (!bouton.closest('form')) trouve.push(`bouton « ${libelle(bouton)} »`);
      });

      return [...new Set(trouve)];
    });

    if (actions.length) ecarts.push(`${entree.chemin} : ${actions.join(' ; ')}`);
  }

  expect(ecarts, 'Écrans proposant une action d\'écriture à un profil en consultation').toEqual([]);
});
