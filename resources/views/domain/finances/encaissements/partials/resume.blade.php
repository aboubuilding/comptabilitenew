{{-- Détail d'un paiement, chargé dans les fenêtres d'encaissement et de consultation --}}
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $eleve = $paiement->inscription?->eleve;
    $encaisse = \App\Domain\Finances\Types\PaiementStatut::ENCAISSE;
    $enAttente = \App\Domain\Finances\Types\PaiementStatut::EN_ATTENTE;
    $totalEncaisse = $paiement->details->filter(fn ($d) => $d->statut_paiement === $encaisse)->sum('montant');
    $monnaie = $paiement->montant_recu !== null ? max(0, $paiement->montant_recu - $totalEncaisse) : null;
@endphp
<div class="resume-paiement" data-paiement="{{ $paiement->id }}" data-reference="{{ $paiement->reference }}"
     data-a-encaisser="{{ $aEncaisser }}" data-mode="{{ $paiement->mode_paiement?->value }}">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <span class="reference">{{ $paiement->reference }}</span>
            <x-badge class="{{ $paiement->statut_paiement?->css() }} ms-1" point>{{ $paiement->statut_paiement?->label() }}</x-badge>
            <div class="text-muted small mt-1">
                Saisi le {{ optional($paiement->date_paiement)->format('d/m/Y') ?? '—' }} par {{ $paiement->comptable?->nom_complet ?: '—' }}
            </div>
        </div>
        <div class="text-end">
            <div class="fw-bold fs-5">{{ $fmt($paiement->details->sum('montant')) }} F</div>
            <div class="text-muted small">
                @if ($paiement->mode_paiement)<i class="fas {{ $paiement->mode_paiement->icone() }} me-1"></i>{{ $paiement->mode_paiement->label() }}@endif
                @if ($paiement->cheque) n° {{ $paiement->cheque->numero }}@if ($paiement->cheque->banque) · {{ $paiement->cheque->banque->nom }}@endif @endif
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3 small">
        <div class="col-md-6">
            <div class="resume-bloc">
                <div class="resume-titre">Élève</div>
                <div class="fw-semibold resume-eleve">{{ $eleve ? $eleve->nom . ' ' . $eleve->prenom : '—' }}</div>
                <div class="text-muted niveau-classe">{{ $eleve->matricule ?? '' }}@if ($paiement->inscription?->niveau) · Niveau {{ $paiement->inscription->niveau->libelle }}@endif @if ($paiement->inscription?->classe) · Classe {{ $paiement->inscription->classe->libelle }}@endif</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="resume-bloc">
                <div class="resume-titre">Payeur</div>
                <div class="fw-semibold">{{ $paiement->payeur ?: '—' }}</div>
                <div class="text-muted">{{ $paiement->telephone_payeur ?: '' }}</div>
            </div>
        </div>
    </div>

    <table class="table table-sm align-middle resume-lignes mb-2">
        <thead>
            <tr><th>Frais / article</th><th class="text-end">Montant</th><th>Statut</th><th>Encaissement</th></tr>
        </thead>
        <tbody>
            @foreach ($paiement->details as $d)
                <tr>
                    <td><span class="nature">{{ $d->type_paiement?->label() ?? '—' }}</span> {{ $d->libelle }}</td>
                    <td class="text-end fw-semibold text-nowrap">{{ $fmt($d->montant) }}</td>
                    <td><x-badge class="{{ $d->statut_paiement?->css() }}" point>{{ $d->statut_paiement?->label() }}</x-badge></td>
                    <td class="small text-muted">
                        @if ($d->statut_paiement === $encaisse)
                            {{ optional($d->date_encaissement)->format('d/m/Y') }}
                            @if ($d->caisse) · {{ $d->caisse->libelle }}@endif
                            @if ($d->caissier) · {{ $d->caissier->nom_complet }}@endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="resume-totaux">
        @if ($totalEncaisse > 0)
            <div><span>Encaissé</span><strong class="text-success">{{ $fmt($totalEncaisse) }} F</strong></div>
            @if ($paiement->montant_recu !== null)
                <div><span>Montant reçu</span><strong>{{ $fmt($paiement->montant_recu) }} F</strong></div>
                @if ($monnaie > 0)<div><span>Monnaie rendue</span><strong>{{ $fmt($monnaie) }} F</strong></div>@endif
            @endif
        @endif
        @if ($aEncaisser > 0)
            <div class="a-encaisser"><span>À encaisser</span><strong>{{ $fmt($aEncaisser) }} F</strong></div>
        @endif
    </div>
</div>
