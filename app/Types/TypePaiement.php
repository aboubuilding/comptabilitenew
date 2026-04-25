<?php


namespace App\Types;


/**
 * Liste des différents états des modeles activé, supprimé, ....
 * @author pmazama@gmail.com
 * @copyright HAMZ-Eng 2022
 *
 * @version 1
 */

class TypePaiement
{
    const FRAIS_INSCRIPTION = 1;
    const FRAIS_SCOLARITE = 2;
    const SERVICES = 3;
    const PRODUIT  = 4;
    const LIVRE  = 5;
    const CAUTION = 6;
    const BUS = 7;
    const CANTINE = 8;
    const AUTRES = 9;
    const FRAIS_ASSURANCE = 10;
   

    const FRAIS_EXTRA_SCOLAIRE = 11;
    const FRAIS_EXAMEN = 12;


    public static function getTypePaiement( $type) {

        $type_libelle = '';

        if($type == TypePaiement::FRAIS_ASSURANCE){

            $type_libelle = 'Frais d assurance';
        }

        if($type == TypePaiement::FRAIS_INSCRIPTION){

            $type_libelle = 'Frais d inscription';
        }


        if($type == TypePaiement::FRAIS_SCOLARITE){

            $type_libelle = 'Frais de scolarité ';
        }


        if($type == TypePaiement::CANTINE){

            $type_libelle = 'Cantine ';
        }

        if($type == TypePaiement::BUS){

            $type_libelle = 'Bus ';
        }

        if($type == TypePaiement::LIVRE){

            $type_libelle = 'Livre ';
        }

        if($type == TypePaiement::FRAIS_EXTRA_SCOLAIRE){

            $type_libelle = 'Frais extra scolaire  ';
        }


        if($type == TypePaiement::LIVRE){

            $type_libelle = 'Livre ';
        }

        if($type == TypePaiement::PRODUIT){

            $type_libelle = 'Produit ';
        }


        return $type_libelle;



    }






}
