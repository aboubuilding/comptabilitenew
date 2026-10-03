/**
 * Environnement des tests E2E : base dédiée, port dédié, aucun envoi d'email.
 * Ces variables priment sur le .env (Laravel ne remplace pas une variable déjà définie).
 */
export const PORT = Number(process.env.E2E_PORT ?? 8123);
export const BASE_URL = `http://127.0.0.1:${PORT}`;
export const BASE_DE_DONNEES = 'comptabilite_e2e';

/** Binaire PHP (celui du PATH par défaut ; E2E_PHP pour en imposer un autre). */
export const PHP = process.env.E2E_PHP ?? 'php';

/** Client mysql de Laragon, utilisé pour créer la base si elle n'existe pas. */
export const MYSQL = process.env.E2E_MYSQL ?? 'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe';

export function envE2e(): Record<string, string> {
  return {
    ...(process.env as Record<string, string>),
    APP_ENV: 'local',
    APP_DEBUG: 'true',
    APP_URL: BASE_URL,
    DB_CONNECTION: 'mysql',
    DB_DATABASE: BASE_DE_DONNEES,
    MAIL_MAILER: 'log', // aucun email réel ne part pendant les tests
    QUEUE_CONNECTION: 'sync',
    SESSION_DRIVER: 'file',
    CACHE_DRIVER: 'file', // partagé entre requêtes (suivi de la génération de la paie)
  };
}
