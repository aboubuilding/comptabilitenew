<?php


namespace App\Services;

use App\Mail\SendMail;
use App\Models\Annee;
use App\Models\Cheque;
use App\Models\Detail;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\ParentEleve;
use App\Types\StatutPaiement;
use App\Types\TypePaiement;
use Illuminate\Support\Facades\Mail;

class MailService
{

    public static function sendMail($to, $objet, $mailData)
    {

        $email = $to;
        $retour = Mail::to($email)
            ->send(new SendMail($objet, $mailData));

        return $retour;
    }

    public static function sendChequeMail($id)
    {
        $cheque_id = $id;
        $cheque = Cheque::rechercheChequeById($cheque_id);

        $paiement = Paiement::recherchePaiementById($cheque->paiement_id);
        $inscription = Inscription::rechercheInscriptionById($paiement->inscription_id);
        $parent = ParentEleve::rechercheParentEleveById($inscription->parent_id);
        $annee = Annee::rechercheAnneeById($inscription->annee_id);


        $details = Detail::getListe($paiement->id);

        $name = "Recu" . $paiement->reference;

        $montant_deja_payer = Detail::getMontantTotal($annee->id, null, TypePaiement::FRAIS_SCOLARITE, $inscription->id, null, StatutPaiement::ENCAISSE);


        $montant_scolarite = $inscription->frais_scolarite;
        $reste = $montant_scolarite - $montant_scolarite * $inscription->taux_remise / 100 - $montant_deja_payer;



        $objet = "Paiement " . $name . " ". $inscription->eleve->prenom . ' ' . $inscription->eleve->nom;

        $mailData = [
            'title' => $objet,
            'body' => "Bonjour Mr/Mme " . $parent->nom_parent . ' ' . $parent->prenom_parent . ", Veuillez trouver ci-dessous votre facture de paiement. Vous pouvez passer au service comptabilité pour avoir la version papier du reçu en presentant la référence du paiement (" . $paiement->reference . ").",
            'paiement' => $paiement,
            'inscription' => $inscription,
            'annee' => $annee,
            'details' => $details,
            'montant_deja_payer' => $montant_deja_payer,
            'montant_scolarite' => $montant_scolarite,
            'reste' => $reste,
            'cheque' => $cheque,
            'name' => $name,
        ];

        MailService::sendMail($parent->email, $objet, $mailData);
    }
}
