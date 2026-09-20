-- =====================================================================
-- SCRIPT DE CORRECTION — Dates invalides (0000-00-00) dans la base
-- gestiondemo
--
-- À exécuter UNE SEULE FOIS, sur la base déjà importée, avant de
-- relancer `php artisan migrate`.
--
-- Contexte : l'ancien serveur MySQL qui a produit ce dump acceptait
-- silencieusement '0000-00-00' comme valeur de date. Le MySQL/MariaDB
-- actuel (mode strict) le rejette dès qu'une opération ALTER TABLE
-- doit reconstruire la table concernée (ex: ajout d'une contrainte de
-- clé étrangère), même si la colonne modifiée n'est pas celle qui
-- porte la date invalide.
--
-- Toutes les colonnes ci-dessous sont déclarées "DEFAULT NULL" dans
-- le schéma : remplacer 0000-00-00 par NULL est donc sans risque et
-- sans perte d'information (une date à zéro n'a jamais rien signifié).
--
-- Comment l'utiliser :
--   1. Ouvrir phpMyAdmin
--   2. Sélectionner la base "gestiondemo" (ou son nom réel en local)
--   3. Onglet "SQL"
--   4. Coller l'intégralité de ce script et cliquer sur "Exécuter"
--   5. Relancer : php artisan migrate
-- =====================================================================

-- Désactive temporairement le mode strict pour CETTE SESSION uniquement
-- (nécessaire : MySQL en mode strict refuse même de COMPARER une colonne
-- à la valeur littérale '0000-00-00', pas seulement de l'écrire).
-- N'affecte ni la configuration globale du serveur, ni les autres
-- connexions ; redevient actif dès la fermeture de cette session.
SET SESSION sql_mode = '';

-- Table details
UPDATE `details` SET `date_paiement` = NULL
  WHERE `date_paiement` = '0000-00-00';

UPDATE `details` SET `date_encaissement` = NULL
  WHERE `date_encaissement` = '0000-00-00';

-- Table eleves
UPDATE `eleves` SET `date_naissance` = NULL
  WHERE `date_naissance` = '0000-00-00';

-- Table inscriptions
UPDATE `inscriptions` SET `date_inscription` = NULL
  WHERE `date_inscription` = '0000-00-00';

UPDATE `inscriptions` SET `date_validation` = NULL
  WHERE `date_validation` = '0000-00-00 00:00:00';

-- Table paiements
UPDATE `paiements` SET `date_paiement` = NULL
  WHERE `date_paiement` = '0000-00-00';

-- Table caisses
UPDATE `caisses` SET `date_ouverture` = NULL
  WHERE `date_ouverture` = '0000-00-00 00:00:00';

UPDATE `caisses` SET `date_cloture` = NULL
  WHERE `date_cloture` = '0000-00-00 00:00:00';

-- =====================================================================
-- Vérification (optionnelle) — doit renvoyer 0 sur chaque ligne
-- une fois le script exécuté avec succès.
-- =====================================================================
SELECT
  (SELECT COUNT(*) FROM `details`      WHERE `date_paiement`     = '0000-00-00') AS details_date_paiement,
  (SELECT COUNT(*) FROM `details`      WHERE `date_encaissement` = '0000-00-00') AS details_date_encaissement,
  (SELECT COUNT(*) FROM `eleves`       WHERE `date_naissance`    = '0000-00-00') AS eleves_date_naissance,
  (SELECT COUNT(*) FROM `inscriptions` WHERE `date_inscription`  = '0000-00-00') AS inscriptions_date_inscription,
  (SELECT COUNT(*) FROM `inscriptions` WHERE `date_validation`   = '0000-00-00 00:00:00') AS inscriptions_date_validation,
  (SELECT COUNT(*) FROM `paiements`    WHERE `date_paiement`     = '0000-00-00') AS paiements_date_paiement,
  (SELECT COUNT(*) FROM `caisses`      WHERE `date_ouverture`    = '0000-00-00 00:00:00') AS caisses_date_ouverture,
  (SELECT COUNT(*) FROM `caisses`      WHERE `date_cloture`      = '0000-00-00 00:00:00') AS caisses_date_cloture;