<?php

namespace App\Services;

use App\Repositories\Eloquent\StockActuelRepository;
use App\Repositories\Eloquent\DetailRepository;
use App\Repositories\Eloquent\ChequeRepository;
use App\Repositories\Eloquent\AbonnementBusRepository;
use App\Repositories\Eloquent\InscriptionCantineRepository;
use App\Repositories\Eloquent\PrevisionRepository;
use App\Repositories\Eloquent\InscriptionRepository;
use Carbon\Carbon;

class AlertService
{
    protected ?int $anneeId;
    protected StockActuelRepository $stockRepo;
    protected DetailRepository $detailPaiementRepo;
    protected ChequeRepository $chequeRepo;
    protected AbonnementBusRepository $abonnementBusRepo;
    protected InscriptionCantineRepository $cantineRepo;
    protected PrevisionRepository $previsionRepo;
    protected InscriptionRepository $inscriptionRepo;

    public function __construct(
        StockActuelRepository $stockRepo,
        DetailRepository $detailPaiementRepo,
        ChequeRepository $chequeRepo,
        AbonnementBusRepository $abonnementBusRepo,
        InscriptionCantineRepository $cantineRepo,
        PrevisionRepository $previsionRepo,
        InscriptionRepository $inscriptionRepo
    ) {
        $this->stockRepo = $stockRepo;
        $this->detailPaiementRepo = $detailPaiementRepo;
        $this->chequeRepo = $chequeRepo;
        $this->abonnementBusRepo = $abonnementBusRepo;
        $this->cantineRepo = $cantineRepo;
        $this->previsionRepo = $previsionRepo;
        $this->inscriptionRepo = $inscriptionRepo;
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
        $stocks = $this->stockRepo->activeQuery()
            ->with(['produit', 'magasin'])
            ->where('quantite', '>', 0)
            ->whereColumn('quantite', '<=', 'seuil_alerte')
            ->get();

        return $stocks->map(fn($s) => [
            'type'    => 'stock_bas',
            'message' => "Stock bas : {$s->produit->libelle} ({$s->magasin->libelle}) – reste {$s->quantite} {$s->produit->unite_base}",
            'niveau'  => $s->quantite == 0 ? 'danger' : 'warning',
            'lien'    => route('admin.stock-boutique.index') . "?produit_id={$s->produit_id}",
        ])->toArray();
    }

    /**
     * Paiements en attente (détails non encaissés)
     */
    private function getPendingPaymentAlerts(): array
    {
        $pending = $this->detailPaiementRepo->activeQuery()
            ->with(['paiement.inscription.eleve'])
            ->where('annee_id', $this->anneeId)
            ->where('statut_paiement', 0)
            ->whereDate('date_paiement', '<=', Carbon::now()->subDays(7))
            ->limit(10)
            ->get();

        return $pending->map(fn($d) => [
            'type'    => 'paiement_attente',
            'message' => "Paiement en attente d'encaissement : {$d->libelle} ({$d->montant} FCFA) – Élève {$d->paiement->inscription->eleve->nom}",
            'niveau'  => 'warning',
            'lien'    => route('admin.paiements.index') . "?search={$d->paiement->reference}",
        ])->toArray();
    }

    /**
     * Chèques émis depuis plus de 15 jours et non encaissés
     */
    private function getUncashedChequeAlerts(): array
    {
        $cheques = $this->chequeRepo->activeQuery()
            ->with('paiement')
            ->where('statut', 0) // non encaissé
            ->where('date_emission', '<=', Carbon::now()->subDays(15))
            ->get();

        return $cheques->map(fn($c) => [
            'type'    => 'cheque_impaye',
            'message' => "Chèque non encaissé : N°{$c->numero} de {$c->emetteur}, émis le {$c->date_emission}",
            'niveau'  => 'danger',
            'lien'    => route('admin.cheques.index') . "?search={$c->numero}",
        ])->toArray();
    }

    /**
     * Échéances de prévisions (dans les 30 jours)
     */
    private function getUpcomingPrevisionAlerts(): array
    {
        $previsions = $this->previsionRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('date_prevision', '>=', Carbon::now())
            ->where('date_prevision', '<=', Carbon::now()->addDays(30))
            ->orderBy('date_prevision')
            ->get();

        return $previsions->map(fn($p) => [
            'type'    => 'echeance_prevision',
            'message' => "Échéance prévue le {$p->date_prevision->format('d/m/Y')} : {$p->libelle} – {$p->montant} FCFA",
            'niveau'  => 'info',
            'lien'    => route('admin.previsions.index') . "?search={$p->libelle}",
        ])->toArray();
    }

    /**
     * Abandons récents (moins de 7 jours)
     */
    private function getRecentAbandonAlerts(): array
    {
        $abandons = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut_abandon', 1)
            ->where('date_abandon', '>=', Carbon::now()->subDays(7))
            ->with('eleve')
            ->get();

        return $abandons->map(fn($i) => [
            'type'    => 'abandon_recent',
            'message' => "Élève abandonné : {$i->eleve->nom} {$i->eleve->prenom} (motif : {$i->motif_abandon})",
            'niveau'  => 'danger',
            'lien'    => route('admin.inscriptions.show', $i->eleve_id),
        ])->toArray();
    }

    /**
     * Impayés bus (reste > 0)
     */
    private function getBusImpayes(): array
    {
        // On suppose que le modèle AbonnementBus a un attribut montant_reste
        // Si non, adapter la logique
        $abonnements = $this->abonnementBusRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->with('inscription.eleve')
            ->get()
            ->filter(fn($a) => ($a->montant_reste ?? 0) > 0)
            ->take(5);

        return $abonnements->map(fn($a) => [
            'type'    => 'impaye_bus',
            'message' => "Impayé bus : {$a->inscription->eleve->nom} – reste {$a->montant_reste} FCFA",
            'niveau'  => 'warning',
            'lien'    => route('admin.bus.show', $a->id),
        ])->toArray();
    }

    /**
     * Impayés cantine (reste > 0)
     */
    private function getCantineImpayes(): array
    {
        $inscriptions = $this->cantineRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->with('inscription.eleve')
            ->get()
            ->filter(fn($c) => ($c->montant_reste ?? 0) > 0)
            ->take(5);

        return $inscriptions->map(fn($c) => [
            'type'    => 'impaye_cantine',
            'message' => "Impayé cantine : {$c->inscription->eleve->nom} – reste {$c->montant_reste} FCFA",
            'niveau'  => 'warning',
            'lien'    => route('admin.cantine.show', $c->id),
        ])->toArray();
    }
}
