import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { COMPTES, type Profil } from './comptes';

export type EntreeMenu = {
  menu: string;
  groupe: string;
  libelle: string;
  route: string;
  chemin: string;
  permission: string;
  autorise: boolean;
};

/** Les 6 menus du cahier des charges (§ 2), dans l'ordre de la barre de navigation. */
export const MENUS = [
  'Scolarité',
  'Finances & Comptabilité',
  'Logistique & Achats',
  'Services aux Élèves',
  'Ressources Humaines',
  'Administration',
] as const;

/** Menu attendu pour un profil, exporté par e2e/support/exporter-menu.php avant l'exécution. */
export function menuDe(profil: Profil): EntreeMenu[] {
  const contenu = JSON.parse(readFileSync(join(__dirname, '..', '.cache', 'menu.json'), 'utf8'));
  return contenu[COMPTES[profil]] as EntreeMenu[];
}
