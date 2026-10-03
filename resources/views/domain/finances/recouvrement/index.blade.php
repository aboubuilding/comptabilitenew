@extends('admin.layouts.app')

@section('title', 'Recouvrement')
@section('page_icon', 'fa-hand-holding-usd')
@section('page_title', 'Recouvrement des impayés')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Recouvrement</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.recouvrement.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    @can('recouvrement.gerer')
        <button type="button" class="btn btn-nouveau" id="btnRecalculer">
            <i class="fas fa-sync-alt me-1"></i> Recalculer
        </button>
    @endcan
@endsection

@section('css')
<style>
    .reco-page { --rc-bg-soft:#faf6ee; --rc-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--rc-border); border-radius:12px; padding:1.05rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.green { background:rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.red   { background:rgba(200,30,58,.10); color:var(--school-red); }
    .stat-icon.blue  { background:rgba(37,99,235,.10); color:#2563eb; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; line-height:1.2; color:var(--school-ink); }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--rc-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--rc-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }

    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background:var(--school-red-dark); color:#fff; }
    .compteur-badge { display:inline-block; background:var(--rc-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--rc-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--rc-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--rc-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-reco thead tr { background:var(--rc-bg-soft); }
    .table-reco thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--rc-border); white-space:nowrap; }
    .table-reco tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--rc-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-reco tbody tr:hover { background:#fdfaf5; }

    .dossier-id { font-family:monospace; background:var(--rc-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-size:.75rem; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.positif { color:#1c7a4d; }
    .num.negatif { color:var(--school-red); }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.encours      { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.echeancier   { background:#e0ecfc; color:#2563eb; }
    .badge-statut.solde        { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.irrecouvrable{ background:#eceff2; color:#5b6b7a; }
    .badge-statut.contentieux  { background:#fdecea; color:var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="reco-page">

    {{-- Cartes stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-folder-open"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['en_cours'] }}</div>
                    <div class="stat-label">Dossiers en cours</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['echeancier'] }}</div>
                    <div class="stat-label">Échéanciers acceptés</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-gavel"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['contentieux'] }}</div>
                    <div class="stat-label">Contentieux</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($montants['total_restant'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Reste à recouvrer</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="N° inscription..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="nature" class="form-select filtre-input">
                        <option value="">— Nature —</option>
                        @foreach ($natures as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['nature'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="annee_id" class="form-select filtre-input">
                        <option value="">— Année —</option>
                        @foreach ($annees as $a)
                            <option value="{{ $a->id }}" @selected(($filters['annee_id'] ?? '') == $a->id)>{{ $a->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('finances.recouvrement.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-reco align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Dossier</th>
                            <th>Élève</th>
                            <th>Nature</th>
                            <th class="text-end">Initial</th>
                            <th class="text-end">Recouvré</th>
                            <th class="text-end">Restant</th>
                            <th>Statut</th>
                            <th>Ouvert le</th>
                            <th>Dernière relance</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($dossiers as $d)
                        <tr>
                            <td class="ps-4"><span class="dossier-id">#{{ $d->id }}</span></td>
                            <td class="text-sm">
                                @php $eleve = $d->inscription?->eleve; @endphp
                                @if ($eleve)
                                    <div class="fw-semibold">{{ $eleve->nom }} {{ $eleve->prenom }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">
                                        {{ $eleve->matricule }}@if ($d->inscription?->classe) · {{ $d->inscription->classe->libelle }}@endif
                                    </div>
                                @else
                                    <span class="text-muted">Inscription #{{ $d->inscription_id }}</span>
                                @endif
                            </td>
                            <td class="text-sm">{{ $d->nature?->label() }}</td>
                            <td class="num">{{ number_format((float) $d->montant_initial_du, 2, ',', ' ') }}</td>
                            <td class="num positif">{{ number_format((float) $d->montant_recouvre, 2, ',', ' ') }}</td>
                            <td class="num negatif">{{ number_format($d->montant_restant, 2, ',', ' ') }}</td>
                            <td>
                                <span class="badge-statut {{ $d->statut?->css() }}">
                                    <span class="badge-dot"></span>{{ $d->statut?->label() }}
                                </span>
                            </td>
                            <td class="text-sm text-muted">{{ optional($d->date_ouverture)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                @php $relance = $d->derniereRelance(); @endphp
                                @if ($relance)
                                    {{ optional($relance->date_relance)->format('d/m/Y') }}
                                    <div class="text-muted" style="font-size:.72rem;">{{ $relance->resultat?->label() ?? 'Résultat non renseigné' }}</div>
                                @else
                                    <span class="text-muted">Aucune</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.recouvrement.show', $d) }}">
                                                <i class="fas fa-eye"></i> Voir le dossier
                                            </a>
                                        </li>
                                        @can('recouvrement.relancer')
                                            @if (! $d->isClos())
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('finances.recouvrement.show', $d) }}#relance">
                                                        <i class="fas fa-bullhorn"></i> Ajouter une relance
                                                    </a>
                                                </li>
                                            @endif
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><div class="empty-state"><i class="fas fa-hand-holding-usd"></i><p>Aucun dossier de recouvrement.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $dossiers->appends(request()->query())->links('vendor.pagination.custom') }}</div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#btnRecalculer').on('click', function () {
        if (!confirm('Recalculer toutes les situations de recouvrement maintenant ?')) return;

        $.ajax({
            url: '{{ route('finances.recouvrement.recalculer') }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                window.showToastThenReload(res.message || 'Recalcul terminé.', 'success');
            },
            error: function () {
                window.showToast("Erreur lors du recalcul.", 'error');
            }
        });
    });
});
</script>
@endpush