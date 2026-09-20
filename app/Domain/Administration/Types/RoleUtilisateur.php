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
    case Administrateur                  = 1;
    case Directeur                       = 2;
    case Comptable                       = 3;
    case Caissier                        = 4;
    case AgentInscriptions               = 5;
    case ResponsableAchatsStock          = 6;
    case ResponsableBoutique             = 7;
    case ResponsableTransport            = 8;
    case ResponsableCantine              = 9;
    case Bibliothecaire                  = 10;
    case ResponsableRhPaie               = 11;
    case ResponsableActivitesEvenements  = 12;

    public function label(): string
    {
        return match ($this) {
            self::Administrateur                => 'Administrateur',
            self::Directeur                      => 'Directeur',
            self::Comptable                      => 'Comptable',
            self::Caissier                       => 'Caissier(ère)',
            self::AgentInscriptions              => 'Agent des inscriptions',
            self::ResponsableAchatsStock         => 'Responsable Achats & Stock',
            self::ResponsableBoutique            => 'Responsable Boutique / Point de vente',
            self::ResponsableTransport           => 'Responsable Transport scolaire',
            self::ResponsableCantine             => 'Responsable Cantine',
            self::Bibliothecaire                 => 'Bibliothécaire',
            self::ResponsableRhPaie              => 'Responsable RH / Paie',
            self::ResponsableActivitesEvenements => 'Responsable Activités & Événements',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Administrateur                => 'badge-danger',
            self::Directeur                      => 'badge-primary',
            self::Comptable                      => 'badge-success',
            self::Caissier                       => 'badge-warning',
            self::AgentInscriptions              => 'badge-info',
            self::ResponsableAchatsStock         => 'badge-secondary',
            self::ResponsableBoutique            => 'badge-secondary',
            self::ResponsableTransport           => 'badge-dark',
            self::ResponsableCantine             => 'badge-dark',
            self::Bibliothecaire                 => 'badge-info',
            self::ResponsableRhPaie              => 'badge-success',
            self::ResponsableActivitesEvenements => 'badge-warning',
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
            self::Administrateur, self::Directeur                       => 'Transversal',
            self::Comptable, self::Caissier                              => 'Finances',
            self::AgentInscriptions                                      => 'Scolarite',
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