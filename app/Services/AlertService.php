<?php
namespace App\Services;

use App\Models\StockActuel;
use App\Models\DetailPaiement;
use App\Models\Cheque;
use App\Models\AbonnementBus;
use App\Models\InscriptionCantine;
use App\Models\Prevision;
use Carbon\Carbon;

class AlertService
{
    protected $anneeId;

    public function __construct()
    {
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Récupère toutes les alertes
     */
    public function getAllAlerts(): array
    {
        return [
            'stock_bas'             => $this->getStockAlerts(),
            'paiements_en_attente'  => $this->getPendingPaymentAlerts(),
            'cheques_non_encaisses' => $this->getUncashedChequeAlerts(),
            'echeances_previsions'  => $this->getUpcomingPrevisionAlerts(),
            'abandons_recents'      => $this->getRecentAbandonAlerts(),
            'impayes_bus'           => $this->getBusImpayes(),
            'impayes_cantine'       => $this->getCantineImpayes(),
        ];
    }

    /**
     * Alertes stock bas (produits dans les boutiques/entrepôts)
     */
    private function getStockAlerts(): array
    {
        $stocks = StockActuel::with(['produit', 'magasin'])
            ->where('quantite', '>', 0)
            ->whereColumn('quantite', '<=', 'seuil_alerte')
            ->get();

        return $stocks->map(fn($s) => [
            'type'      => 'stock_bas',
            'message'   => "Stock bas : {$s->produit->libelle} ({$s->magasin->libelle}) – reste {$s->quantite} {$s->produit->unite_base}",
            'niveau'    => $s->quantite == 0 ? 'danger' : 'warning',
            'lien'      => route('admin.stock-boutique.index') . "?produit_id={$s->produit_id}",
        ])->toArray();
    }

    /**
     * Paiements en attente (détails non encaissés)
     */
    private function getPendingPaymentAlerts(): array
    {
        $pending = DetailPaiement::with(['paiement.inscription.eleve'])
            ->where('annee_id', $this->anneeId)
            ->where('statut_paiement', 0)
            ->whereDate('date_paiement', '<=', Carbon::now()->subDays(7))
            ->limit(10)
            ->get();

        return $pending->map(fn($d) => [
            'type'      => 'paiement_attente',
            'message'   => "Paiement en attente d'encaissement : {$d->libelle} ({$d->montant} FCFA) – Élève {$d->paiement->inscription->eleve->nom}",
            'niveau'    => 'warning',
            'lien'      => route('admin.paiements.index') . "?search={$d->paiement->reference}",
        ])->toArray();
    }

    /**
     * Chèques émis depuis plus de 15 jours et non encaissés
     */
    private function getUncashedChequeAlerts(): array
    {
        $cheques = Cheque::with('paiement')
            ->where('statut', 0) // non encaissé
            ->where('date_emission', '<=', Carbon::now()->subDays(15))
            ->get();

        return $cheques->map(fn($c) => [
            'type'      => 'cheque_impaye',
            'message'   => "Chèque non encaissé : N°{$c->numero} de {$c->emetteur}, émis le {$c->date_emission}",
            'niveau'    => 'danger',
            'lien'      => route('admin.cheques.index') . "?search={$c->numero}",
        ])->toArray();
    }

    /**
     * Échéances de prévisions (dans les 30 jours)
     */
    private function getUpcomingPrevisionAlerts(): array
    {
        $previsions = Prevision::where('annee_id', $this->anneeId)
            ->where('date_prevision', '>=', Carbon::now())
            ->where('date_prevision', '<=', Carbon::now()->addDays(30))
            ->orderBy('date_prevision')
            ->get();

        return $previsions->map(fn($p) => [
            'type'      => 'echeance_prevision',
            'message'   => "Échéance prévue le {$p->date_prevision->format('d/m/Y')} : {$p->libelle} – {$p->montant} FCFA",
            'niveau'    => 'info',
            'lien'      => route('admin.previsions.index') . "?search={$p->libelle}",
        ])->toArray();
    }

    /**
     * Abandons récents (moins de 7 jours)
     */
    private function getRecentAbandonAlerts(): array
    {
        $abandons = \App\Models\Inscription::where('annee_id', $this->anneeId)
            ->where('statut_abandon', 1)
            ->where('date_abandon', '>=', Carbon::now()->subDays(7))
            ->with('eleve')
            ->get();

        return $abandons->map(fn($i) => [
            'type'      => 'abandon_recent',
            'message'   => "Élève abandonné : {$i->eleve->nom} {$i->eleve->prenom} (motif : {$i->motif_abandon})",
            'niveau'    => 'danger',
            'lien'      => route('admin.inscriptions.show', $i->eleve_id),
        ])->toArray();
    }

    /**
     * Impayés bus (reste > 0)
     */
    private function getBusImpayes(): array
    {
        $impayes = AbonnementBus::where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->get()
            ->filter(fn($a) => $a->montant_reste > 0)
            ->take(5);

        return $impayes->map(fn($a) => [
            'type'      => 'impaye_bus',
            'message'   => "Impayé bus : {$a->inscription->eleve->nom} – reste {$a->montant_reste} FCFA",
            'niveau'    => 'warning',
            'lien'      => route('admin.bus.show', $a->id),
        ])->toArray();
    }

    private function getCantineImpayes(): array
    {
        $impayes = InscriptionCantine::where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->get()
            ->filter(fn($c) => $c->montant_reste > 0)
            ->take(5);

        return $impayes->map(fn($c) => [
            'type'      => 'impaye_cantine',
            'message'   => "Impayé cantine : {$c->inscription->eleve->nom} – reste {$c->montant_reste} FCFA",
            'niveau'    => 'warning',
            'lien'      => route('admin.cantine.show', $c->id),
        ])->toArray();
    }
}