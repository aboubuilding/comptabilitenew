@extends('admin.layouts.app')

@section('title', 'Prêts en cours')
@section('page_icon', 'fa-exchange-alt')
@section('page_title', 'Prêts de la bibliothèque')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.bibliotheque.livres.index') }}">Bibliothèque</a></li>
    <li class="breadcrumb-item active">Prêts</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.bibliotheque.prets.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('services-eleves.bibliotheque.livres.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-book me-1"></i> Catalogue
    </a>
    @can('bibliotheque.gerer')
        <form action="{{ route('services-eleves.bibliotheque.prets.recalculerRetards') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-outline-scolarite btn-sm rounded-pill px-3" type="submit">
                <i class="fas fa-sync-alt me-1"></i> Recalculer retards
            </button>
        </form>
    @endcan
@endsection

@section('css')
<style>
    /* Réutilise les classes biblio-page */
    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.encours { background:#e0ecfc; color:#2563eb; }
    .badge-statut.rendu   { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.retard  { background:#fdecea; color:var(--school-red); font-weight:700; }
    .badge-statut.perdu   { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .alerte-retards {
        background:#fdecea; border-left:3px solid var(--school-red);
        border-radius:8px; padding:.85rem 1rem; margin-bottom:1rem;
        font-size:.85rem; color:var(--school-red-dark);
    }
</style>
@endsection

@section('contenu')
<div class="biblio-page">

    @if ($retards->isNotEmpty())
        <div class="alerte-retards">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>{{ $retards->count() }} prêt(s)</strong> en retard :
            @foreach ($retards->take(5) as $r)
                <span class="badge-statut retard ms-1" style="background:#fff;border:1px solid var(--school-red);">
                    {{ $r->livre?->titre }} — {{ $r->inscription?->eleve?->nom }} ({{ $r->jours_retard }} j)
                </span>
            @endforeach
            @if ($retards->count() > 5)
                <span class="ms-1">+{{ $retards->count() - 5 }} autre(s)</span>
            @endif
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-exchange-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-label">Prêts au total</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-hand-holding"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['en_cours'] }}</div>
                    <div class="stat-label">En cours</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['en_retard'] }}</div>
                    <div class="stat-label">En retard</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['rendus'] }}</div>
                    <div class="stat-label">Rendus</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Livre, élève, matricule..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="statut_pret" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut_pret'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="en_retard" value="1" id="enRetard" @checked($filters['en_retard'] ?? false)>
                        <label class="form-check-label small" for="enRetard">En retard uniquement</label>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('services-eleves.bibliotheque.prets.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-livres align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Livre</th>
                            <th>Élève</th>
                            <th>Date prêt</th>
                            <th>Retour prévu</th>
                            <th>Retour réel</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($prets as $pret)
                        <tr>
                            <td class="ps-4">
                                <span class="titre-livre">{{ $pret->livre?->titre ?? '—' }}</span>
                                <span class="sub">{{ $pret->livre?->auteur?->nom_prenom }}</span>
                            </td>
                            <td class="text-sm">
                                <div class="fw-semibold">
                                    {{ trim(($pret->inscription?->eleve?->nom ?? '') . ' ' . ($pret->inscription?->eleve?->prenom ?? '')) }}
                                </div>
                                <div class="text-xs text-muted">
                                    {{ $pret->inscription?->eleve?->matricule }}
                                    @if ($pret->inscription?->classe) · {{ $pret->inscription->classe->libelle }} @endif
                                </div>
                            </td>
                            <td class="text-sm">{{ optional($pret->date_pret)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                {{ optional($pret->date_retour_pret)->format('d/m/Y') }}
                                @if ($pret->isEnRetard())
                                    <div class="text-xs" style="color:var(--school-red);font-weight:700;">
                                        {{ $pret->jours_retard }} j de retard
                                    </div>
                                @endif
                            </td>
                            <td class="text-sm">{{ optional($pret->date_retour_reel)->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <span class="badge-statut {{ $pret->statut_pret?->css() }}">
                                    <span class="badge-dot"></span>{{ $pret->statut_pret?->label() }}
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @if ($pret->isEnCours())
                                            @can('bibliotheque.gerer')
                                                <li>
                                                    <button class="dropdown-item btn-retour"
                                                            data-id="{{ $pret->id }}"
                                                            data-livre="{{ $pret->livre?->titre }}"
                                                            data-bs-toggle="modal" data-bs-target="#retourModal">
                                                        <i class="fas fa-check"></i> Retour
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-danger btn-perdu"
                                                            data-id="{{ $pret->id }}"
                                                            data-livre="{{ $pret->livre?->titre }}"
                                                            data-bs-toggle="modal" data-bs-target="#perduModal">
                                                        <i class="fas fa-times"></i> Marquer perdu
                                                    </button>
                                                </li>
                                            @endcan
                                        @else
                                            <li>
                                                <span class="dropdown-item text-muted small" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i> Prêt clôturé
                                                </span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-exchange-alt"></i><p>Aucun prêt.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $prets->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modal retour --}}
    @can('bibliotheque.gerer')
    <div class="modal fade" id="retourModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-livres">
                <form id="retour-form" method="POST" action="">
                    @csrf
                    <div class="modal-header modal-header-livres">
                        <h5 class="modal-title"><i class="fas fa-check me-2"></i>Enregistrer le retour</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-2">Retour du livre : <strong id="retour-livre">—</strong></p>
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Date de retour réel</label>
                                <input type="date" name="date_retour_reel" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Observations</label>
                                <textarea name="observations" rows="2" class="form-control" maxlength="2000"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-check me-1"></i> Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- Modal perdu --}}
    @can('bibliotheque.gerer')
    <div class="modal fade" id="perduModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-livres">
                <form id="perdu-form" method="POST" action="">
                    @csrf
                    <div class="modal-header" style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;">
                        <h5 class="modal-title"><i class="fas fa-times me-2"></i>Marquer comme perdu</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p>Marquer le livre <strong id="perdu-livre">—</strong> comme perdu ?</p>
                        <p class="text-muted small">L'exemplaire sera définitivement retiré du stock.</p>
                        <div class="form-section">
                            <label class="form-label">Motif / observations <span class="text-danger">*</span></label>
                            <textarea name="observations" rows="3" class="form-control" required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    $('.btn-retour').on('click', function () {
        $('#retour-livre').text($(this).data('livre'));
        $('#retour-form').attr('action', `{{ url('services-eleves/bibliotheque/prets') }}/${$(this).data('id')}/retour`);
    });

    $('#retour-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#retourModal').modal('hide');
                window.showToastThenReload(res.message || 'Retour enregistré.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });

    $('.btn-perdu').on('click', function () {
        $('#perdu-livre').text($(this).data('livre'));
        $('#perdu-form').attr('action', `{{ url('services-eleves/bibliotheque/prets') }}/${$(this).data('id')}/perdu`);
    });

    $('#perdu-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#perduModal').modal('hide');
                window.showToastThenReload(res.message || 'Prêt marqué perdu.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });
});
</script>
@endpush