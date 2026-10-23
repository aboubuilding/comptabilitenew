<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation automatique des dépenses
    |--------------------------------------------------------------------------
    | Une dépense d'un montant strictement inférieur à ce seuil (en F CFA) est
    | validée dès sa saisie ; au-delà, elle attend la validation de la
    | Direction. Une fois validée, le caissier la décaisse depuis sa caisse
    | ouverte (écran Décaissements).
    */
    'seuil_validation_depense' => (float) env('SEUIL_VALIDATION_DEPENSE', 150000),

    /*
    |--------------------------------------------------------------------------
    | Envoi réel des relances (email, SMS)
    |--------------------------------------------------------------------------
    | Les relances sont d'abord enregistrées « à envoyer ». Leur envoi réel
    | (bouton « Envoyer la sélection ») reste désactivé tant que ce paramètre
    | vaut false — en développement notamment : on garde les messages et leurs
    | statuts. L'email passe par la configuration MAIL_* ; aucune passerelle SMS
    | n'est encore branchée.
    */
    'relances_envoi_actif' => (bool) env('RELANCES_ENVOI_ACTIF', false),

];
