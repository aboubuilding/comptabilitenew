<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Annee;
use App\Models\Cheque;
use App\Models\Detail;
use App\Models\FraisEcole;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\ParentEleve;
use App\Services\MailService;
use App\Types\StatutPaiement;
use App\Types\TypePaiement;
use App\Types\TypeStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChequeController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];

        $cheques = Cheque::getListe();

        foreach ($cheques as $cheque) {
            $data[]  = array(

                "id" => $cheque->id,
                "numero" => $cheque->numero == null ? ' ' : $cheque->numero,
                "emetteur" => $cheque->emetteur == null ? ' ' : $cheque->emetteur,
                "reference" => $cheque->paiement_id == null ? ' ' : $cheque->paiement->reference,
                "statut" => $cheque->statut == null ? ' ' : $cheque->statut,
                "banque" => $cheque->banque_id == null ? ' ' : $cheque->banque->nom,
                "date_emission" => $cheque->date_emission == null ? ' ' : $cheque->date_emission,
                "montant_cheque" => $cheque->montant == null ? 0 : $cheque->montant,
                "montant_paiement" => $cheque->paiement_id == null ? 0 : $cheque->paiement->montant,
                "eleve" => $cheque->paiement->inscription->eleve->nom . ' ' . $cheque->paiement->inscription->eleve->prenom,


            );
        }

        return view('admin.cheque.index')->with(
            [
                'data' => $data,

            ]


        );
    }






    public function encaisser(Request $request)
    {
        $cheque = Cheque::rechercheChequeById($request->id);
        $paiement = $cheque->paiement;
        $inscription = $paiement->inscription;
        $montant = 0;
        $remise = 0;
        $details = Detail::getListe($cheque->paiement_id);
        DB::beginTransaction();
        try {

            if ($paiement && $paiement->statut_paiement != StatutPaiement::ENCAISSE) {
                foreach ($details as  $detail) {

                    $montant_detail = $detail->montant;

                    if ($detail->type_paiement == TypePaiement::FRAIS_SCOLARITE) {


                        if (Helper::payer_en_totalite($inscription->id, (float)$detail->montant, $paiement)) {

                            $remise =  (int)env('default_remise');

                            $service_ecolage = FraisEcole::getPrix(
                                TypePaiement::FRAIS_SCOLARITE,
                                null,
                                $inscription->niveau_id,
                                $inscription->annee_id
                            );



                            $montant_detail -= $service_ecolage->montant * $remise / 100;

                            if ($montant_detail < 0) {
                                $montant_detail = 0;
                                $remise = 0;
                            }

                            $remise += (int)$inscription->taux_remise;

                            $detail->montant = $montant_detail;
                            $detail->save();
                        }
                    }

                    $montant += $montant_detail;
                }

                $inscription->taux_remise = $remise;
                $inscription->save();

                $paiement->montant = $montant;
                $paiement->statut_paiement = StatutPaiement::ENCAISSE;
                $paiement->save();

                $details2 = Detail::getListe($paiement->id);

                foreach ($details2 as $detail) {
                    $detail->statut_paiement = StatutPaiement::ENCAISSE;
                    $detail->date_encaissement = now();
                    $detail->save();
                }

                $cheque->statut = StatutPaiement::ENCAISSE;
                $cheque->date_encaissement = now();
                $cheque->save();
                DB::commit();

                MailService::sendChequeMail($cheque->id);

                return response()->json(['code' => 1, 'msg' => 'Cheque encaissé avec succès']);
            } else {
                return response()->json(['code' => 0, 'msg' => 'Paiement déjà encaissé']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['code' => 0, 'msg' => 'Erreur lors de l\'encaissement du chèque', 'error' => $e->getMessage()]);
        }
    }


    public function supprimer(Request $request)
    {
        $cheque = Cheque::rechercheChequeById($request->id);

        if ($cheque) {
            $paiement = Paiement::recherchePaiementById($cheque->paiement_id);
            if ($paiement) {
                $details = Detail::getListe($paiement->id);
                foreach ($details as $detail) {
                    $detail->etat = TypeStatus::SUPPRIME;
                    $detail->save();
                }
                $paiement->etat = TypeStatus::SUPPRIME;
                $paiement->save();
            }
            $cheque->etat = TypeStatus::SUPPRIME;
            $cheque->save();

            return response()->json(['code' => 1, 'msg' => 'Cheque supprimé avec succès']);
        } else {
            return response()->json(['code' => 0, 'msg' => 'Cheque non trouvé']);
        }
    }


    public function imprimerFacture($id)
    {
        $cheque = Cheque::rechercheChequeById($id);

        $paiement = Paiement::recherchePaiementById($cheque->paiement_id);
        $inscription = Inscription::rechercheInscriptionById($paiement->inscription_id);
        $parent = ParentEleve::rechercheParentEleveById($inscription->parent_id);
        $annee = Annee::rechercheAnneeById($inscription->annee_id);


        $details = Detail::getListe($paiement->id);

        $name = "Recu" . $paiement->reference;

        $montant_deja_payer = Detail::getMontantTotal($annee->id, null, TypePaiement::FRAIS_SCOLARITE, $inscription->id, null, StatutPaiement::ENCAISSE);


        $montant_scolarite = $inscription->frais_scolarite;
        $reste = $montant_scolarite - $montant_scolarite * $inscription->taux_remise / 100 - $montant_deja_payer;


        $pdf = Pdf::loadView('admin.cheque.pdf', compact(
            'cheque',
            'paiement',
            'inscription',
            'parent',
            'annee',
            'details',
            'name',
            'montant_deja_payer',
            'montant_scolarite',
            'reste'
        ));
        return $pdf->stream('facture_' . $cheque->id . '.pdf');
    }

    public function update(Request $request, $id)
    {


        $validator = \Validator::make($request->all(), [

            'numero' => 'required|string|max:25|unique:cheques,numero,' . $id,


        ], [
            'numero.required' => 'Le numero   est obligatoire ',


        ]);

        if (!$validator->passes()) {
            return response()->json(['code' => 0, 'error' => $validator->errors()->toArray()]);
        } else {

            Cheque::updateCheque(

                $request->numero,
                $request->emetteur,
                $request->annee_id,
                $request->paiement_id,
                $request->date_emission,
                $request->statut,
                $request->date_encaissement,
                $request->banque_id,
                $request->montant,

                $id


            );



            return response()->json(['code' => 1, 'msg' => 'Cheque modifiée  avec succès ']);
        }
    }






    /**
     * Afficher  un Cheque
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {

        $cheque = Cheque::rechercheChequeById($id);


        return response()->json(['code' => 1, 'Cheque' => $cheque]);
    }



    /**
     * Supprimer   une  Cheque scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {



        $delete = Cheque::deleteCheque($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Cheque  supprimée ";
        } else {
            $success = true;
            $message = "Cheque  non trouvée ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }


    public function sendFacture(Request $request, $id)
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



        $objet = "Paiement " . $name;

        $mailData = [
            'title' => $objet,
            'body' => "Bonjour Mr/Mme " . $parent->nom_parent . ' ' . $parent->prenom_parent . ', Veuillez trouver ci-dessous votre facture de paiement.',
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

        return response()->json(['code' => 1, 'msg' => 'Facture envoyée avec succès']);
    }
}
