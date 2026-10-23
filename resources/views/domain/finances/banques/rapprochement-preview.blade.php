@extends('admin.layouts.app')

@section('title', 'Rapprochement bancaire — ' . $banque->nom)
@section('page_icon', 'fa-file-import')
@section('page_title', 'Rapprochement bancaire — vérification')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.banques.index') }}">Banques</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.banques.show', $banque) }}">{{ $banque->nom }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.banques.rapprochement.index', $banque) }}">Rapprochement</a></li>
    <li class="breadcrumb-item active">Vérification</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.banques.rapprochement.index', $banque) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Importer un autre relevé
    </x-bouton>
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow: 0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--eim-border) !important; }
    .stat-box { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:12px; padding:.9rem 1rem; }
    .stat-box .stat-value { font-size:1.4rem; font-weight:700; color: var(--school-ink); }
    .stat-box .stat-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color: var(--school-muted); }
    .table-rapprochement th { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color: var(--school-muted); font-weight:600; }
    .table-rapprochement td { font-size:.86rem; vertical-align: middle; }
    .num { text-align:right; font-variant-numeric: tabular-nums; }
    .pastille { display:inline-block; width:9px; height:9px; border-radius:50%; margin-right:.4rem; }
    .btn-primary-red { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.6rem 1.5rem; font-size:.9rem; }
    .btn-primary-red:hover { color:#fff; box-shadow: 0 4px 12px rgba(227,30,36,.3); }
</style>
@endsection

@section('contenu')
@php
    $matched   = $analyse['matched'] ?? [];
    $ambiguous = $analyse['ambiguous'] ?? [];
    $unmatched = $analyse['unmatched'] ?? [];
    $fmt = fn ($montant) => number_format((float) $montant, 2, ',', ' ');
    $jour = fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('d/m/Y');
    $index = 0;
@endphp

{{-- Résumé de l'import --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-box">
            <div class="stat-value">{{ $lignesTotal }}</div>
            <div class="stat-label">Lignes lues</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-box">
            <div class="stat-value" style="color:var(--eim-success);">{{ count($matched) }}</div>
            <div class="stat-label">Correspondances exactes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-box">
            <div class="stat-value" style="color:var(--eim-gold-deep);">{{ count($ambiguous) }}</div>
            <div class="stat-label">À vérifier</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-box">
            <div class="stat-value" style="color:#b13b3b;">{{ count($unmatched) }}</div>
            <div class="stat-label">Sans chèque correspondant</div>
        </div>
    </div>
</div>

@if ($lignesIgnorees > 0)
    <div class="alert alert-warning py-2 small">
        <i class="fas fa-exclamation-triangle me-1"></i>
        {{ $lignesIgnorees }} ligne(s) du fichier ont été ignorées (numéro, date ou montant manquant).
    </div>
@endif

<form method="POST" action="{{ route('finances.banques.rapprochement.appliquer', $banque) }}" id="rapprochement-form">
    @csrf

    {{-- 1. Correspondances exactes : même numéro et même montant --}}
    <x-carte class="mb-4">
        <div class="card-header bg-white py-3 px-4">
            <h6 class="mb-0"><span class="pastille" style="background:var(--eim-success);"></span>Correspondances exactes</h6>
            <small class="text-muted">Même numéro et même montant qu'un chèque émis. Décochez une ligne pour ne pas la rapprocher.</small>
        </div>
        <x-tableau class="table-rapprochement mb-0">
                <thead>
                    <tr>
                        <th style="width:44px;"></th>
                        <th>N° chèque</th>
                        <th>Émetteur</th>
                        <th>Date d'encaissement (relevé)</th>
                        <th class="num">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matched as $paire)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input paire-toggle" checked data-index="{{ $index }}">
                                <input type="hidden" name="paires[{{ $index }}][cheque_id]" value="{{ $paire['cheque']->id }}" data-paire="{{ $index }}">
                                <input type="hidden" name="paires[{{ $index }}][date]" value="{{ $paire['ligne']->dateEncaissement }}" data-paire="{{ $index }}">
                            </td>
                            <td class="fw-semibold">{{ $paire['ligne']->numero }}</td>
                            <td>{{ $paire['cheque']->emetteur }}</td>
                            <td>{{ $jour($paire['ligne']->dateEncaissement) }}</td>
                            <td class="num fw-semibold">{{ $fmt($paire['ligne']->montant) }}</td>
                        </tr>
                        @php $index++; @endphp
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucune correspondance exacte.</td></tr>
                    @endforelse
                </tbody>
            </x-tableau>
    </x-carte>

    {{-- 2. Lignes ambiguës : numéro OU montant identique, pas les deux --}}
    @if (count($ambiguous) > 0)
        <x-carte class="mb-4">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><span class="pastille" style="background:var(--eim-gold-deep);"></span>Lignes à vérifier</h6>
                <small class="text-muted">Le numéro ou le montant correspond à un chèque émis, mais pas les deux. Choisissez le chèque concerné, ou laissez « Ne pas rapprocher ».</small>
            </div>
            <x-tableau class="table-rapprochement mb-0">
                    <thead>
                        <tr>
                            <th>Ligne du relevé</th>
                            <th>Date</th>
                            <th class="num">Montant</th>
                            <th style="width:45%;">Chèque à rapprocher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ambiguous as $cas)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $cas['ligne']->numero }}
                                    @if ($cas['ligne']->libelle)
                                        <div class="text-muted small fw-normal">{{ $cas['ligne']->libelle }}</div>
                                    @endif
                                </td>
                                <td>{{ $jour($cas['ligne']->dateEncaissement) }}</td>
                                <td class="num">{{ $fmt($cas['ligne']->montant) }}</td>
                                <td>
                                    <select class="form-select form-select-sm paire-select" data-index="{{ $index }}">
                                        <option value="">Ne pas rapprocher</option>
                                        @foreach ($cas['candidats'] as $candidat)
                                            <option value="{{ $candidat->id }}">
                                                N° {{ $candidat->numero }} — {{ $candidat->emetteur }} — {{ $fmt($candidat->montant) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="paires[{{ $index }}][cheque_id]" value="" data-paire="{{ $index }}" disabled>
                                    <input type="hidden" name="paires[{{ $index }}][date]" value="{{ $cas['ligne']->dateEncaissement }}" data-paire="{{ $index }}" disabled>
                                </td>
                            </tr>
                            @php $index++; @endphp
                        @endforeach
                    </tbody>
                </x-tableau>
        </x-carte>
    @endif

    {{-- 3. Lignes sans chèque --}}
    @if (count($unmatched) > 0)
        <x-carte class="mb-4">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><span class="pastille" style="background:#b13b3b;"></span>Lignes sans chèque correspondant</h6>
                <small class="text-muted">Aucun chèque émis de cette banque ne porte ce numéro ni ce montant. Ces lignes ne seront pas rapprochées.</small>
            </div>
            <x-tableau class="table-rapprochement mb-0">
                    <thead>
                        <tr>
                            <th>N° sur le relevé</th>
                            <th>Libellé</th>
                            <th>Date</th>
                            <th class="num">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($unmatched as $ligne)
                            <tr>
                                <td class="fw-semibold">{{ $ligne->numero }}</td>
                                <td>{{ $ligne->libelle ?? '—' }}</td>
                                <td>{{ $jour($ligne->dateEncaissement) }}</td>
                                <td class="num">{{ $fmt($ligne->montant) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-tableau>
        </x-carte>
    @endif

    <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small"><span id="nb-selection">0</span> chèque(s) seront marqués comme encaissés.</span>
        <button type="submit" class="btn btn-primary-red" id="btn-appliquer">
            <i class="fas fa-check me-1"></i> Valider le rapprochement
        </button>
    </div>
</form>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('rapprochement-form');
    const compteur = document.getElementById('nb-selection');
    const bouton = document.getElementById('btn-appliquer');

    // Un champ désactivé n'est pas envoyé : c'est ainsi qu'une paire est écartée.
    function champs(index) {
        return form.querySelectorAll('[data-paire="' + index + '"]');
    }

    function majCompteur() {
        const nb = form.querySelectorAll('input[name$="[cheque_id]"]:not([disabled])').length;
        compteur.textContent = nb;
        bouton.disabled = nb === 0;
    }

    form.querySelectorAll('.paire-toggle').forEach(function (case_) {
        case_.addEventListener('change', function () {
            champs(case_.dataset.index).forEach(c => c.disabled = !case_.checked);
            majCompteur();
        });
    });

    form.querySelectorAll('.paire-select').forEach(function (liste) {
        liste.addEventListener('change', function () {
            champs(liste.dataset.index).forEach(function (c) {
                c.disabled = liste.value === '';
                if (c.name.endsWith('[cheque_id]')) c.value = liste.value;
            });
            majCompteur();
        });
    });

    majCompteur();
});
</script>
@endpush
