<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Barème IRPP — tranches progressives
    |--------------------------------------------------------------------------
    | Chaque tranche contient :
    |   - min  : borne inférieure (incluse)
    |   - max  : borne supérieure (incluse), null = tranche supérieure
    |   - taux : taux d'imposition en pourcentage
    |
    | Modifier ce fichier selon la juridiction fiscale applicable.
    | Les montants sont exprimés dans la devise locale (FCFA par défaut).
    */

    'tranches' => [
        ['min' => 0,         'max' => 60000,    'taux' => 0],
        ['min' => 60001,     'max' => 150000,   'taux' => 3],
        ['min' => 150001,    'max' => 300000,   'taux' => 10],
        ['min' => 300001,    'max' => 500000,   'taux' => 15],
        ['min' => 500001,    'max' => 1000000,  'taux' => 20],
        ['min' => 1000001,   'max' => null,     'taux' => 25],
    ],

    /*
    |--------------------------------------------------------------------------
    | Abattement forfaitaire (déduction avant barème)
    |--------------------------------------------------------------------------
    | Ex : 20 % du salaire brut dans certains pays. Mettre 0 si non applicable.
    | Actuellement c'est 0 car l'abattement est saisi manuellement dans
    | `deductions.forfait_professionnel`.
    */

    'abattement_pourcentage' => 0,

    /*
    |--------------------------------------------------------------------------
    | Réduction pour charges de famille
    |--------------------------------------------------------------------------
    | Certains pays appliquent une réduction d'impôt par personne à charge.
    | Ici, on reste sur un système où les charges familiales sont déjà
    | déduites manuellement dans `deductions.charges_familiale`.
    | Cette option est prévue pour être activée si besoin.
    */

    'reduction_par_charge' => 0,

];