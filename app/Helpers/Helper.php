<?php

namespace App\Helpers;

use App\Models\Annee;
use App\Models\Detail;
use App\Models\Espace;
use App\Models\FraisEcole;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\Paiement;
use App\Types\ModePaiement;
use App\Types\StatutPaiement;
use App\Types\TypePaiement;
use App\Types\TypeStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rules\In;

class Helper
{

    /**
     * Voir si le parent veut payer en totalité
     
     * @param  int $inscription_id
     * @param  int $montant



     * @return boolean
     */
    public static function payer_en_totalite($inscription_id, $montant, $paiement = null)
    {
        $retour = false;

        $inscription = Inscription::rechercheInscriptionById($inscription_id);
        $annee = Annee::rechercheAnneeById($inscription->annee_id);
        $niveau = Niveau::rechercheNiveauById($inscription->niveau_id);

        // Par défaut : date actuelle
        $dateReference = now();

        // Si un paiement est passé → utiliser created_at si chèque
        if ($paiement) {
            $dateReference = $paiement->mode_paiement === ModePaiement::CHEQUE
                ? $paiement->created_at
                : now();
        }

        $service_ecolage = FraisEcole::getPrix(
            TypePaiement::FRAIS_SCOLARITE,
            null,
            $inscription->niveau_id,
            $inscription->annee_id
        );

        $deja_paye = Detail::getMontantTotal(
            null,
            null,
            TypePaiement::FRAIS_SCOLARITE,
            $inscription_id,
            null,
            StatutPaiement::ENCAISSE
        );

        // Convertir la date d’échéance en Carbon
        $date_echeance = Carbon::parse($annee->date_rentree)->startOfDay();

        // Vérifier les conditions
        $montantAttendu = $service_ecolage->montant - ($service_ecolage->montant * $inscription->taux_remise / 100);

        if (
            $montant >= ($montantAttendu - $deja_paye) // Montant suffisant
            && $dateReference->lt($date_echeance)     // Paiement avant l’échéance
            && $niveau->programme == 1                // Programme concerné
        ) {
            $retour = true;
        }

        return $retour;
    }


    /**
     * Retour la différence en mois entre 2 date
     
     * @param  string $date1
     * @param  string $date2


     * @return int 
     */
    public static function diffDate()
    {
        $session = session()->get('LoginUser');

        $annee_id = $session['annee_id'];
        $annee = Annee::rechercheAnneeById($annee_id);

        $dateEcheance = Carbon::parse($annee->date_rentree)->startOfDay();

        if (now()->lt($dateEcheance)) {
            $date1 = $dateEcheance;
        } else {
            $date1 = Carbon::today();
        }

        $date2 = Carbon::parse($annee->date_fin);

        // Différence en mois tronqués
        $diffMonths = $date1->diffInMonths($date2);

        // Vérifie s'il reste des jours au-delà des mois complets
        $daysReste = $date1->copy()->addMonths($diffMonths)->diffInDays($date2, false);

        if ($daysReste > 10) {
            $diffMonths += 1; // On arrondit en excès
        }

        return $diffMonths;
    }



    public static function updateMontantPaiements()
    {
        // Récupère tous les paiements avec leurs détails
        $paiements = Paiement::with('detailsValides')->get();

        foreach ($paiements as $paiement) {
            $total = $paiement->detailsValides->sum('montant');
            $paiement->montant = $total;
            $paiement->save();
        }

        return "Mise à jour réussie des montants pour les paiements valides.";
    }

    public static function isNightTime()
    {
        $hour = now()->hour;
        return $hour >= 19 || $hour < 0;
    }
}
