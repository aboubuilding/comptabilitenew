{{-- Dépenses imputées à une ligne budgétaire, avec la caisse qui a servi à les décaisser --}}
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    // Imputées : les dépenses réellement décaissées (les rejetées et celles en attente ne comptent pas)
    $retenues = $depenses->filter(fn ($d) => ! $d->isRejetee() && $d->isReglee());
    $montantDe = fn ($d) => (float) ($d->montant_decaisse ?? $d->montant);
    $total = $retenues->sum($montantDe);
    $enAttente = $depenses->filter(fn ($d) => ! $d->isRejetee() && ! $d->isReglee());
    $reste = (float) $ligne->montant_previsionnel - $total;
@endphp
<div class="contenu-depenses-ligne" data-ligne="{{ $ligne->id }}">
    <div class="d-flex flex-wrap gap-3 mb-3 small">
        <div>Montant global : <strong>{{ $fmt($ligne->montant_previsionnel) }} F</strong></div>
        <div>Dépenses imputées (décaissées) : <strong>{{ $fmt($total) }} F</strong></div>
        @if ($enAttente->isNotEmpty())
            <div class="text-muted">En attente de décaissement, non imputé : {{ $fmt($enAttente->sum('montant')) }} F</div>
        @endif
        <div>
            @if ($reste >= 0)
                Reste : <strong style="color:var(--eim-success);">{{ $fmt($reste) }} F</strong>
            @else
                <strong style="color:var(--school-red);">Dépassement : {{ $fmt(-$reste) }} F</strong>
            @endif
        </div>
    </div>

    <x-tableau class="table-sm align-middle mb-0" id="depenses-ligne">
        <thead>
            <tr>
                <th>Dépense</th>
                <th>Bénéficiaire</th>
                <th>Statut</th>
                <th>Caisse du décaissement</th>
                <th class="text-end">Montant</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($depenses as $d)
            <tr class="@if ($d->isRejetee() || ! $d->isReglee()) text-muted @endif">
                <td>
                    <div class="fw-semibold">{{ $d->libelle }}</div>
                    <div class="cell-sub">{{ optional($d->date_depense)->format('d/m/Y') }}@if ($d->auteur) · saisie par {{ $d->auteur->nom }} {{ $d->auteur->prenom }}@endif</div>
                </td>
                <td>{{ $d->beneficiaire ?: '—' }}</td>
                <td class="small">
                    {{ $d->isReglee() ? 'Décaissée' : ($d->statut_depense?->label() ?? '—') }}
                    @if ($d->isRejetee())
                        <div class="cell-sub">non comptée</div>
                    @elseif (! $d->isReglee())
                        <div class="cell-sub">non imputée tant qu'elle n'est pas décaissée</div>
                    @endif
                </td>
                <td>
                    @if ($d->caisse)
                        <div><i class="fas fa-cash-register me-1 text-muted"></i>{{ $d->caisse->libelle }}</div>
                        <div class="cell-sub">le {{ optional($d->date_reglement)->format('d/m/Y') }}@if ($d->regleur) · {{ $d->regleur->nom }} {{ $d->regleur->prenom }}@endif</div>
                    @else
                        <span class="text-muted small">Pas encore décaissée</span>
                    @endif
                </td>
                <td class="text-end">
                    <span class="num">{{ $fmt($montantDe($d)) }} F</span>
                    @if ($d->montant_decaisse !== null && abs((float) $d->montant_decaisse - (float) $d->montant) > 0.5)
                        <div class="cell-sub">dépense : {{ $fmt($d->montant) }} F</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Aucune dépense n'est imputée à cette ligne.</td></tr>
        @endforelse
        </tbody>
    </x-tableau>
</div>
