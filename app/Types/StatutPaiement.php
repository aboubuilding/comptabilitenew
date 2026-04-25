<?php


namespace App\Types;


/**
 * Liste des différents états des années scolaires nouveau, ouverte , ....
 * @author aboukadani@gmail.com
 * @copyright Abou-Eng 2023
 *
 * @version 1
 */

 class StatutPaiement
 {
     const NON_ENCAISSE =1;
     const ENCAISSE =2;
     const EN_ATTENTE_ANNULATION =3;
     const ANNULES =4;


 }
