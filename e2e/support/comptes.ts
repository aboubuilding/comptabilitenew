/**
 * Comptes créés par database/seeders/E2eSeeder.php : un par profil du cahier des charges (§ 3).
 */
export const MOT_DE_PASSE = 'E2e-Secret-1';

export const COMPTES = {
  admin: 'e2e.admin',
  directeur: 'e2e.directeur',
  comptable: 'e2e.comptable',
  caissier: 'e2e.caissier',
  secretaire: 'e2e.secretaire',
  controleur: 'e2e.controleur',
  recouvrement: 'e2e.recouvrement',
  achats: 'e2e.achats',
  boutique: 'e2e.boutique',
  transport: 'e2e.transport',
  cantine: 'e2e.cantine',
  biblio: 'e2e.biblio',
  rh: 'e2e.rh',
  activites: 'e2e.activites',
  adjoint: 'e2e.adjoint',
  enseignant: 'e2e.enseignant',
} as const;

export type Profil = keyof typeof COMPTES;
