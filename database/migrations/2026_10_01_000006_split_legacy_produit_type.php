<?php

use App\Domain\Finances\Types\TypeDetail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Dans la base historique, le code 4 (PRODUIT) servait de fourre-tout :
     * activités, cantine, transport, examens, livres, sorties... y côtoient
     * les vrais produits (tenues, tissus, kits).
     *
     * On reclasse chaque frais de type 4 d'après son libellé, puis les lignes
     * de paiement (et souscriptions) de type 4 prennent le type de leur frais.
     * Un libellé qu'aucune règle ne reconnaît reste PRODUIT.
     *
     * Première règle reconnue = type retenu : l'ordre compte (un libellé
     * "CANTINE_DERNIERE_TRANCHE" doit être vu comme cantine avant tout).
     */
    private const REGLES = [
        '/CANTINE|GOUTER/u'                                  => TypeDetail::CANTINE,
        '/(^|[^A-Z])BUS([^A-Z]|$)|TRANSPORT/u'               => TypeDetail::BUS,
        '/EXAMEN|EPREUVES|(^|[^A-Z])(BAC|DNB)([^A-Z]|$)/u'   => TypeDetail::FRAIS_EXAMEN,
        '/LIVRE|MANUEL.NUM/u'                                => TypeDetail::LIVRE,
        '/SORTIE/u'                                          => TypeDetail::EVENEMENT,
        '/SUIVI|LOCATION|PHOTO/u'                            => TypeDetail::SERVICE,
        '/\(AES\)|ACTIVITE|BADMINTON|BASKETBALL|FOOTBALL|GENIE MATHEMATIQUE|HANDBALL|PATINAGE|POTERIE|RUGBY|TAEKWONDO|TENNIS|THEATRE|TRAVAUX MANUELS|VOLLEYBALL/u'
                                                             => TypeDetail::ACTIVITE,
    ];

    public function up(): void
    {
        $produit = TypeDetail::PRODUIT->value;

        // 1. Les frais, d'après leur libellé
        $parType = [];
        foreach (DB::table('frais_ecoles')->where('type_paiement', $produit)->get(['id', 'libelle']) as $frais) {
            $type = $this->classer((string) $frais->libelle);
            if ($type !== TypeDetail::PRODUIT) {
                $parType[$type->value][] = $frais->id;
            }
        }

        foreach ($parType as $type => $ids) {
            foreach (array_chunk($ids, 500) as $lot) {
                DB::table('frais_ecoles')->whereIn('id', $lot)->update(['type_paiement' => $type]);

                // 2. Les lignes de type 4 suivent le frais auquel elles sont rattachées
                DB::table('details')
                    ->where('type_paiement', $produit)
                    ->whereIn('frais_ecole_id', $lot)
                    ->update(['type_paiement' => $type]);

                DB::table('souscriptions')
                    ->where('type_paiement', $produit)
                    ->whereIn('frais_ecole_id', $lot)
                    ->update(['type_paiement' => $type]);
            }
        }
    }

    public function down(): void
    {
        // Irréversible : après reclassement, rien ne distingue une ligne venue de l'ancien code 4
    }

    private function classer(string $libelle): TypeDetail
    {
        $libelle = mb_strtoupper(trim($libelle));

        foreach (self::REGLES as $motif => $type) {
            if (preg_match($motif, $libelle)) {
                return $type;
            }
        }

        return TypeDetail::PRODUIT;
    }
};
