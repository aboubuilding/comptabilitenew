<?php

namespace Tests\Domain\Finances;

use App\Domain\Finances\DTO\PaiementData;
use App\Domain\Finances\DTO\PanierLigneData;
use App\Domain\Finances\Models\Paiement;
use App\Domain\Finances\Services\PaiementService;
use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.2 Paiements
 * et règles transversales « Cycle de vie d'un paiement » et
 * « Paiement multi-natures (panier) ».
 */
class PaiementsTest extends DomainTestCase
{
    private PaiementService $service;
    private int $comptableId;
    private int $inscriptionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaiementService::class);
        $this->comptableId = $this->creerUtilisateur()->id;
        $this->inscriptionId = $this->creerInscription()->id;
    }

    private function data(array $lignes): PaiementData
    {
        return new PaiementData(
            $this->inscriptionId, 'KOFFI Yao', '90000000', ModePaiement::ESPECES->value, $lignes, $this->comptableId
        );
    }

    private function panierMultiNatures(): array
    {
        return [
            new PanierLigneData(TypeDetail::FRAIS_SCOLAIRE, 'Scolarité tranche 1', 225000),
            new PanierLigneData(TypeDetail::CANTINE, 'Cantine septembre', 45000),
            new PanierLigneData(TypeDetail::PRODUIT, 'T-shirt M', 13000, quantite: 2, prixUnitaire: 6500),
        ];
    }

    public function test_les_statuts_de_paiement_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, PaiementStatut::EN_ATTENTE->value);
        $this->assertSame(2, PaiementStatut::ENCAISSE->value);
        $this->assertSame(3, PaiementStatut::EN_ATTENTE_ANNULATION->value);
        $this->assertSame(4, PaiementStatut::ANNULE->value);
    }

    public function test_un_panier_vide_est_refuse(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->enregistrer($this->data([]));
    }

    public function test_un_paiement_est_enregistre_en_attente_d_encaissement(): void
    {
        $paiement = $this->service->enregistrer($this->data($this->panierMultiNatures()));

        $this->assertSame(PaiementStatut::EN_ATTENTE, $paiement->statut_paiement);
        $this->assertSame($this->comptableId, (int) $paiement->utilisateur_id);
        $this->assertSame($this->annee->id, (int) $paiement->annee_id);
        $this->assertNotEmpty($paiement->reference);
    }

    public function test_un_paiement_multi_natures_est_ventile_en_une_ligne_par_nature(): void
    {
        $paiement = $this->service->enregistrer($this->data($this->panierMultiNatures()));

        $this->assertCount(3, $paiement->details);
        $this->assertEqualsWithDelta(283000, (float) $paiement->montant, 0.01);
        $this->assertEqualsWithDelta(283000, (float) $paiement->details->sum('montant'), 0.01);
        $this->assertEqualsCanonicalizing(
            [TypeDetail::FRAIS_SCOLAIRE, TypeDetail::CANTINE, TypeDetail::PRODUIT],
            $paiement->details->pluck('type_paiement')->all()
        );
        foreach ($paiement->details as $detail) {
            $this->assertSame(PaiementStatut::EN_ATTENTE, $detail->statut_paiement);
            $this->assertSame($this->comptableId, (int) $detail->comptable_id);
            $this->assertNull($detail->date_encaissement);
        }
    }

    public function test_deux_paiements_ont_des_references_differentes(): void
    {
        $premier = $this->service->enregistrer($this->data($this->panierMultiNatures()));
        $second = $this->service->enregistrer($this->data($this->panierMultiNatures()));

        $this->assertNotSame($premier->reference, $second->reference);
    }

    public function test_modifier_un_paiement_en_attente_remplace_ses_lignes(): void
    {
        $paiement = $this->service->enregistrer($this->data($this->panierMultiNatures()));

        $modifie = $this->service->modifier($paiement->id, $this->data([
            new PanierLigneData(TypeDetail::FRAIS_SCOLAIRE, 'Scolarité tranche 1', 200000),
        ]));

        $this->assertEqualsWithDelta(200000, (float) $modifie->montant, 0.01);
        $actives = DB::table('details')->where('paiement_id', $paiement->id)->where('etat', BaseRepository::ACTIF)->get();
        $this->assertCount(1, $actives);
        // Les anciennes lignes sont archivées, pas supprimées
        $this->assertSame(4, DB::table('details')->where('paiement_id', $paiement->id)->count());
    }

    public function test_annuler_un_paiement_en_attente_annule_aussi_ses_lignes(): void
    {
        $paiement = $this->service->enregistrer($this->data($this->panierMultiNatures()));

        $annule = $this->service->annuler($paiement->id, 'Erreur de saisie', $this->comptableId);

        $this->assertSame(PaiementStatut::ANNULE, $annule->statut_paiement);
        $this->assertSame('Erreur de saisie', $annule->motif_suppression);
        foreach ($annule->details as $detail) {
            $this->assertSame(PaiementStatut::ANNULE, $detail->statut_paiement);
        }
    }

    public function test_un_paiement_encaisse_ne_peut_plus_etre_modifie(): void
    {
        $paiement = $this->service->enregistrer($this->data($this->panierMultiNatures()));
        Paiement::whereKey($paiement->id)->update(['statut_paiement' => PaiementStatut::ENCAISSE->value]);

        $this->expectException(RuntimeException::class);

        $this->service->modifier($paiement->id, $this->data($this->panierMultiNatures()));
    }

    public function test_un_paiement_encaisse_ne_peut_plus_etre_annule(): void
    {
        $paiement = $this->service->enregistrer($this->data($this->panierMultiNatures()));
        Paiement::whereKey($paiement->id)->update(['statut_paiement' => PaiementStatut::ENCAISSE->value]);

        $this->expectException(RuntimeException::class);

        $this->service->annuler($paiement->id, 'Trop tard', $this->comptableId);
    }
}
