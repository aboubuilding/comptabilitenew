{{--
    Paiements liés à une souscription (fenêtre « Voir les paiements ») :
    rappel du montant attendu, de l'encaissé et du reste, puis les lignes.
--}}
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $etats = [
        'solde'   => ['Paiement terminé', 'encaisse'],
        'partiel' => ['Paiement en cours', 'attente'],
        'impaye'  => ['Aucun encaissement', 'retard'],
    ];
    [$etatLibelle, $etatClasse] = $etats[$souscription->etat_paiement];
    $eleve = $souscription->inscription?->eleve;
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3" id="paiements-resume">
    <div>
        <div class="fw-semibold">{{ $eleve?->nom }} {{ $eleve?->prenom }}</div>
        <div class="text-xs text-muted">{{ $souscription->type_paiement?->label() }} — {{ $souscription->intitule }}</div>
    </div>
    <x-badge class="{{ $etatClasse }}" point>{{ $etatLibelle }}</x-badge>
</div>

<div class="row g-2 mb-3">
    <div class="col-4"><div class="resume-montant"><span>Attendu</span><strong>{{ $fmt($souscription->montant_net) }} F</strong></div></div>
    <div class="col-4"><div class="resume-montant"><span>Encaissé</span><strong class="text-success">{{ $fmt($souscription->montant_paye) }} F</strong></div></div>
    <div class="col-4"><div class="resume-montant"><span>Reste à percevoir</span><strong class="text-danger">{{ $fmt($souscription->reste) }} F</strong></div></div>
</div>

<x-tableau class="table-sousc align-middle mb-0" id="table-paiements-lies">
    <thead>
        <tr>
            <th>Paiement</th>
            <th>Libellé</th>
            <th class="text-end">Montant</th>
            <th>État</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($paiements as $d)
        @php
            $statut = \App\Domain\Finances\Types\PaiementStatut::tryFrom((int) $d->getRawOriginal('statut_paiement'));
            $mode = \App\Domain\Finances\Types\ModePaiement::tryFrom((int) $d->mode_paiement);
        @endphp
        <tr>
            <td>
                <span class="reference">{{ $d->reference ?? '—' }}</span>
                <div class="text-xs text-muted">{{ optional($d->date_paiement)->format('d/m/Y') }}@if ($mode) · {{ $mode->label() }}@endif</div>
            </td>
            <td class="text-sm">
                {{ $d->libelle ?: '—' }}
                @if ($d->payeur)<div class="text-xs text-muted">Payé par {{ $d->payeur }}</div>@endif
            </td>
            <td class="num">{{ $fmt($d->montant) }} F</td>
            <td>
                <x-badge class="{{ $statut?->css() ?? 'annule' }}" point>{{ $statut?->label() ?? '—' }}</x-badge>
                @if ($d->date_encaissement)
                    <div class="text-xs text-muted">le {{ \Illuminate\Support\Carbon::parse($d->date_encaissement)->format('d/m/Y') }}@if ($d->caisse) · {{ $d->caisse }}@endif</div>
                @endif
            </td>
        </tr>
    @empty
        <x-vide colspan="4" icone="fas fa-receipt">Aucun paiement enregistré pour cette souscription.</x-vide>
    @endforelse
    </tbody>
</x-tableau>
