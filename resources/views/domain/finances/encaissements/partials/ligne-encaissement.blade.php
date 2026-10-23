{{-- Une ligne d'encaissement (un paiement encaissé dans une caisse, par un caissier, un jour donné) --}}
@php
    $mode = \App\Domain\Finances\Types\ModePaiement::tryFrom((int) $e->mode_paiement);
    $listeNatures = $natures($e->natures);
    $recuUrl = route('finances.encaissements.paiement.recu', $e->paiement_id);
    $monnaie = $e->montant_recu !== null ? max(0, (float) $e->montant_recu - (float) $e->montant) : 0;
    $caisseOuverteIci = isset($caisseOuverte) && $caisseOuverte && (int) $e->caisse_id === (int) $caisseOuverte->id;
@endphp
<tr data-paiement="{{ $e->paiement_id }}">
    <td class="ps-4 col-ref">
        <span class="reference">{{ $e->reference }}</span>
        <div class="cell-sub mt-1"><i class="far fa-calendar me-1"></i>{{ $e->date_encaissement ? \Illuminate\Support\Carbon::parse($e->date_encaissement)->format('d/m/Y') : '—' }}</div>
        <div class="cell-sub" title="{{ $listeNatures }}">{{ $e->nb_lignes }} ligne(s)</div>
    </td>
    <td class="col-eleve">
        <div class="fw-semibold">{{ $e->eleve_nom ? $e->eleve_nom . ' ' . $e->eleve_prenom : $e->payeur }}</div>
        <div class="cell-sub niveau-classe">{{ $e->matricule }}@if ($e->niveau) · {{ $e->niveau }}@endif @if ($e->classe) · {{ $e->classe }}@endif</div>
        <div class="cell-sub">Payé par {{ $e->payeur }}</div>
    </td>
    <td class="col-detail">
        <div class="fw-semibold"><i class="fas fa-cash-register me-1 text-muted"></i>{{ $e->caisse ?: '—' }}</div>
        <div class="cell-sub"><i class="far fa-user me-1"></i>{{ $e->caissier ?: '—' }}</div>
    </td>
    <td class="text-end col-montant">
        <div class="num">{{ $fmt($e->montant) }} F</div>
        <div class="cell-sub">
            @if ($mode)<i class="fas {{ $mode->icone() }}"></i> {{ $mode->label() }}@endif
            @if ($monnaie > 0) · rendu {{ $fmt($monnaie) }} F @endif
        </div>
    </td>
    <td class="text-center pe-4 text-nowrap">
        <a href="{{ $recuUrl }}" target="_blank" class="btn-imprimer btn-imprimer-recu" title="Imprimer le reçu"><i class="fas fa-print"></i><span class="d-none d-xl-inline ms-1">Reçu</span></a>
        <x-actions class="d-inline-block">
                <li><a href="#" role="button" class="dropdown-item btn-detail" data-paiement="{{ $e->paiement_id }}" data-recu="{{ $recuUrl }}"><i class="fas fa-eye"></i> Détail</a></li>
                @if (! empty($annulable) && $caisseOuverteIci)
                    @can('encaissements.annuler')
                        <li><button type="button" class="dropdown-item text-danger btn-annuler-encaissement" data-paiement="{{ $e->paiement_id }}" data-reference="{{ $e->reference }}"><i class="fas fa-undo"></i> Annuler l'encaissement</button></li>
                    @endcan
                @endif
            </x-actions>
    </td>
</tr>
