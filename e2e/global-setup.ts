import { execFileSync } from 'node:child_process';
import { BASE_DE_DONNEES, MYSQL, PHP, envE2e } from './support/environnement';

/**
 * Avant chaque exécution : la base E2E est recréée par les migrations puis
 * remplie par E2eSeeder. Rien n'est lancé si la base visée n'est pas *_e2e.
 */
export default function globalSetup(): void {
  if (!BASE_DE_DONNEES.endsWith('_e2e')) {
    throw new Error(`Base E2E invalide : ${BASE_DE_DONNEES}`);
  }

  const env = envE2e();
  const artisan = (...args: string[]) =>
    execFileSync(PHP, ['artisan', ...args], { env, stdio: ['ignore', 'pipe', 'pipe'] }).toString();

  execFileSync(
    MYSQL,
    ['-uroot', '-e', `CREATE DATABASE IF NOT EXISTS ${BASE_DE_DONNEES} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`],
    { stdio: ['ignore', 'pipe', 'pipe'] },
  );

  // Garde-fou : on vérifie la base réellement vue par Laravel avant de la vider.
  const vue = artisan('tinker', '--execute', 'echo DB::connection()->getDatabaseName();').trim();
  if (!vue.endsWith(BASE_DE_DONNEES)) {
    throw new Error(`Laravel pointe sur « ${vue} » au lieu de « ${BASE_DE_DONNEES} » : exécution annulée.`);
  }

  artisan('migrate:fresh', '--force');
  artisan('db:seed', '--class=E2eSeeder', '--force');

  // Menu attendu par profil, d'après la matrice des permissions (e2e/.cache/menu.json).
  execFileSync(PHP, ['e2e/support/exporter-menu.php'], { env, stdio: ['ignore', 'pipe', 'pipe'] });
}
