<?php


namespace App\Types;


/**
 * Liste des différents états des modeles activé, supprimé, ....
 * @author pmazama@gmail.com
 * @copyright HAMZ-Eng 2022
 *
 * @version 1
 */

 class StatutCompte
 {
     const EN_ATTENTE_VALIDATION=1;
     const VALIDE=2;
     const SUSPENDU=3;
     const SUPPRIME=4;

 }
