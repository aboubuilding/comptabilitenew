{{-- Contenu d'un paiement réglé par chèque (fenêtre de la fiche banque) --}}
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $eleve = $paiement->inscription?->eleve;
    $statutCheque = $cheque->statut;
@endphp
<div class="contenu-paiement" data-paiement="{{ $paiement->id }}">
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="bloc-info">
                <div class="bloc-titre">Paiement</div>
                <div><span class="reference">{{ $paiement->reference }}</span></div>
                <div class="cell-sub mt-1">Le {{ optional($paiement->date_paiement)->format('d/m/Y') ?? '—' }}@if ($paiement->comptable) · saisi par {{ $paiement->comptable->nom_complet }}@endif</div>
                @if ($paiement->statut_paiement)<div class="cell-sub">{{ $paiement->statut_paiement->label() }}</div>@endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="bloc-info">
                <div class="bloc-titre">Élève / payeur</div>
                <div class="fw-semibold">{{ $eleve ? $eleve->nom . ' ' . $eleve->prenom : ($paiement->payeur ?: '—') }}</div>
                <div class="cell-sub">{{ $eleve?->matricule }}@if ($paiement->inscription?->niveau) · {{ $paiement->inscription->niveau->libelle }}@endif @if ($paiement->inscription?->classe) · {{ $paiement->inscription->classe->libelle }}@endif</div>
                <div class="cell-sub">Payé par {{ $paiement->payeur ?: '—' }}@if ($paiement->telephone_payeur) · {{ $paiement->telephone_payeur }}@endif</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bloc-info">
                <div class="bloc-titre">Chèque adossé</div>
                <div class="fw-semibold">N° {{ $cheque->numero }} — {{ $fmt($cheque->montant) }} F</div>
                @if ($cheque->banque)<div class="cell-sub">{{ $cheque->banque->nom }}</div>@endif
                <div class="cell-sub">Émetteur : {{ $cheque->emetteur ?: '—' }}</div>
                <div class="cell-sub">{{ $statutCheque?->label() ?? '—' }}@if ($cheque->date_encaissement) le {{ $cheque->date_encaissement->format('d/m/Y') }}@endif</div>
            </div>
        </div>
    </div>

    <x-tableau class="table-sm align-middle mb-2" id="lignes-paiement">
        <thead>
            <tr>
                <th>Désignation</th>
                <th>Nature</th>
                <th class="text-end">Montant</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($paiement->details as $d)
                <tr>
                    <td>{{ $d->libelle ?: '—' }}</td>
                    <td><span class="nature">{{ $d->type_paiement?->label() ?? '—' }}</span></td>
                    <td class="text-end num">{{ $fmt($d->montant) }} F</td>
                    <td class="cell-sub">
                        {{ $d->statut_paiement?->label() ?? '—' }}
                        @if ($d->date_encaissement) le {{ \Illuminate\Support\Carbon::parse($d->date_encaissement)->format('d/m/Y') }}@endif
                        @if ($d->caissier) · {{ $d->caissier->nom_complet }}@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted text-center py-3">Ce paiement n'a aucune ligne.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-end fw-bold">Total du paiement</td>
                <td class="text-end num">{{ $fmt($totaux['total']) }} F</td>
                <td class="cell-sub">
                    Encaissé {{ $fmt($totaux['encaisse']) }} F
                    @if ($totaux['en_attente'] > 0) · en attente {{ $fmt($totaux['en_attente']) }} F @endif
                </td>
            </tr>
        </tfoot>
    </x-tableau>
    @if (abs((float) $cheque->montant - $totaux['total']) > 0.5)
        <div class="alert alert-warning small mb-0 py-2" id="ecart-cheque-paiement">
            <i class="fas fa-exclamation-triangle me-1"></i>Le chèque ({{ $fmt($cheque->montant) }} F) ne correspond pas au total du paiement ({{ $fmt($totaux['total']) }} F).
        </div>
    @endif
</div>
