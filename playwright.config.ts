import { defineConfig, devices } from '@playwright/test';
import { BASE_URL, PHP, PORT, envE2e } from './e2e/support/environnement';

/**
 * Tests de bout en bout, organisés selon les 6 menus du cahier des charges
 * (e2e/01-scolarite ... e2e/06-administration).
 *
 *   npm run e2e            tout lancer
 *   npm run e2e -- e2e/02-finances
 *
 * Ils tournent sur la base dédiée comptabilite_e2e, reconstruite à chaque
 * exécution (e2e/global-setup.ts), et sur un serveur PHP lancé pour
 * l'occasion — jamais sur la base ni le serveur de travail.
 */
export default defineConfig({
  testDir: './e2e',
  // Le serveur PHP intégré traite une requête à la fois et les scénarios
  // s'enchaînent sur la même base : exécution strictement séquentielle.
  fullyParallel: false,
  workers: 1,
  retries: 0,
  timeout: 60_000,
  expect: { timeout: 10_000 },
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'e2e/rapport' }]],
  outputDir: 'e2e/resultats',
  globalSetup: './e2e/global-setup.ts',

  use: {
    baseURL: BASE_URL,
    locale: 'fr-FR',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
  },

  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } }],

  webServer: {
    // Serveur PHP intégré plutôt que « artisan serve » : ce dernier efface les
    // variables d'environnement transmises et retomberait sur la base du .env.
    // Le routeur de Laravel cherche index.php dans le dossier courant : on démarre depuis public/.
    command: `"${PHP}" -S 127.0.0.1:${PORT} ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`,
    cwd: './public',
    url: `${BASE_URL}/login`,
    env: envE2e(),
    reuseExistingServer: false,
    timeout: 60_000,
    stdout: 'ignore',
    stderr: 'pipe',
  },
});
