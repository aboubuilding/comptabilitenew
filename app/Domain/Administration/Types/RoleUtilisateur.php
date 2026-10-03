<?php

namespace App\Domain\Administration\Types;

/**
 * Rôles des utilisateurs internes (table `users` — personnel de
 * l'établissement), alignés sur les 13 profils du cahier des charges
 * (§3), à une exception près.
 *
 * Le profil "Parent" n'est volontairement PAS repris ici : le portail
 * parent s'authentifie via la table `comptes` (espace_id/parent_id), pas
 * via `users` — ce sont deux mécanismes d'authentification distincts,
 * pas un rôle parmi d'autres dans ce enum. D'où 12 cases pour 13 profils
 * documentés.
 */
enum RoleUtilisateur: int
{
    // Codes 1 à 7 : ceux de l'ancienne classe App\Types\Role, déjà portés
    // par users.role dans la base historique. 8 y désignait PARENT : laissé
    // libre (voir plus haut). Les profils ajoutés depuis commencent à 9.
    case Administrateur                  = 1;
    case Directeur                       = 2;
    case Comptable                       = 3;
    case AdministrateurAdjoint           = 4;
    case Caissier                        = 5;
    case Secretaire                      = 6;
    case Enseignant                      = 7;
    case AgentInscriptions               = 9;
    case ResponsableAchatsStock          = 10;
    case ResponsableBoutique             = 11;
    case ResponsableTransport            = 12;
    case ResponsableCantine              = 13;
    case Bibliothecaire                  = 14;
    case ResponsableRhPaie               = 15;
    case ResponsableActivitesEvenements  = 16;
    case ControleurGestion               = 17;
    case ChargeRecouvrement              = 18;

    public function label(): string
    {
        return match ($this) {
            self::Administrateur                => 'Administrateur',
            self::Directeur                      => 'Directeur',
            self::Comptable                      => 'Comptable',
            self::AdministrateurAdjoint          => 'Administrateur adjoint',
            self::Secretaire                     => 'Secrétaire',
            self::Enseignant                     => 'Enseignant',
            self::Caissier                       => 'Caissier(ère)',
            self::AgentInscriptions              => 'Agent des inscriptions',
            self::ResponsableAchatsStock         => 'Responsable Achats & Stock',
            self::ResponsableBoutique            => 'Responsable Boutique / Point de vente',
            self::ResponsableTransport           => 'Responsable Transport scolaire',
            self::ResponsableCantine             => 'Responsable Cantine',
            self::Bibliothecaire                 => 'Bibliothécaire',
            self::ResponsableRhPaie              => 'Responsable RH / Paie',
            self::ResponsableActivitesEvenements => 'Responsable Activités & Événements',
            self::ControleurGestion              => 'Contrôleur de gestion',
            self::ChargeRecouvrement             => 'Chargé(e) de recouvrement',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Administrateur                => 'badge-danger',
            self::Directeur                      => 'badge-primary',
            self::Comptable                      => 'badge-success',
            self::AdministrateurAdjoint          => 'badge-danger',
            self::Secretaire                     => 'badge-info',
            self::Enseignant                     => 'badge-secondary',
            self::Caissier                       => 'badge-warning',
            self::AgentInscriptions              => 'badge-info',
            self::ResponsableAchatsStock         => 'badge-secondary',
            self::ResponsableBoutique            => 'badge-secondary',
            self::ResponsableTransport           => 'badge-dark',
            self::ResponsableCantine             => 'badge-dark',
            self::Bibliothecaire                 => 'badge-info',
            self::ResponsableRhPaie              => 'badge-success',
            self::ResponsableActivitesEvenements => 'badge-warning',
            self::ControleurGestion              => 'badge-primary',
            self::ChargeRecouvrement             => 'badge-warning',
        };
    }

    /**
     * Domaine de navigation (les 6 groupes du menu) auquel ce profil est
     * naturellement rattaché — point de départ pour la matrice profil ×
     * action à formaliser avant d'écrire les Policies des 13 modules.
     */
    public function domainePrincipal(): string
    {
        return match ($this) {
            self::Administrateur, self::Directeur,
            self::AdministrateurAdjoint                                  => 'Transversal',
            self::Comptable, self::Caissier,
            self::ControleurGestion, self::ChargeRecouvrement            => 'Finances',
            self::AgentInscriptions, self::Secretaire, self::Enseignant  => 'Scolarite',
            self::ResponsableAchatsStock, self::ResponsableBoutique      => 'Logistique',
            self::ResponsableTransport, self::ResponsableCantine,
            self::Bibliothecaire, self::ResponsableActivitesEvenements  => 'ServicesEleves',
            self::ResponsableRhPaie                                      => 'Rh',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }
}