@extends('admin.layouts.app')

@section('title', 'Catalogue des livres')
@section('page_icon', 'fa-book')
@section('page_title', 'Catalogue de la bibliothèque')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item active">Bibliothèque</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.bibliotheque.livres.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('services-eleves.bibliotheque.prets.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-exchange-alt me-1"></i> Prêts
    </a>
    <a href="{{ route('services-eleves.bibliotheque.referentiels.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-cog me-1"></i> Référentiels
    </a>
    @can('bibliotheque.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouveauLivre"
                data-bs-toggle="modal" data-bs-target="#livreModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau livre
        </button>
    @endcan

    <a href="{{ route('services-eleves.bibliotheque.livres.exportPdf', request()->query()) }}"
   class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
    <i class="fas fa-file-pdf me-1"></i> PDF
</a>
@endsection

@section('css')
<style>
    .biblio-page { --b-bg-soft:#faf6ee; --b-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--b-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-icon.red   { background:rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--b-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--b-border); padding:.55rem .9rem; font-size:.88rem; }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--b-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--b-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--b-border); }

    .table-livres thead tr { background:var(--b-bg-soft); }
    .table-livres thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--b-border); white-space:nowrap; }
    .table-livres tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--b-border); font-size:.9rem; vertical-align:middle; }
    .table-livres tbody tr:hover { background:#fdfaf5; }

    .titre-livre { font-weight:700; color:var(--school-ink); display:block; }
    .sub { font-size:.75rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-dispo { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:700; }
    .badge-dispo.ok   { background:#e3f7ec; color:#1c7a4d; }
    .badge-dispo.low  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-dispo.none { background:#fdecea; color:var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-livres { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-livres { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--b-bg-soft); border:1px solid var(--b-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--b-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--b-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="biblio-page">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['total_livres'] }}</div>
                    <div class="stat-label">Titres au catalogue</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['total_exemplaires'] }}</div>
                    <div class="stat-label">Exemplaires total</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['total_disponibles'] }}</div>
                    <div class="stat-label">Disponibles</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['livres_indisponibles'] }}</div>
                    <div class="stat-label">Titres indisponibles</div>
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
                               placeholder="Titre, auteur, ISBN..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="categorie_livre_id" class="form-select filtre-input">
                        <option value="">— Catégorie —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(($filters['categorie_livre_id'] ?? '') == $c->id)>{{ $c->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="auteur_id" class="form-select filtre-input">
                        <option value="">— Auteur —</option>
                        @foreach ($auteurs as $a)
                            <option value="{{ $a->id }}" @selected(($filters['auteur_id'] ?? '') == $a->id)>{{ $a->nom_prenom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="disponible" class="form-select filtre-input">
                        <option value="">— Tous —</option>
                        <option value="1" @selected(($filters['disponible'] ?? '') == 1)>Disponibles uniquement</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-livres align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Titre</th>
                            <th>Auteur</th>
                            <th>Catégorie</th>
                            <th class="text-center">Disponibilité</th>
                            <th>Emprunteur actuel</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($livres as $livre)
                        @php
                            $dispo = $livre->nombre_disponibles;
                            $total = $livre->nombre_exemplaires;
                            $badge = $dispo === 0 ? 'none' : ($dispo === 1 ? 'low' : 'ok');
                            $label = $dispo === 0 ? 'Indisponible' : ($dispo === 1 ? 'Dernier exemplaire' : 'Disponible');
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('services-eleves.bibliotheque.livres.show', $livre) }}" class="text-decoration-none">
                                    <span class="titre-livre">{{ $livre->titre }}</span>
                                    <span class="sub">
                                        @if ($livre->numero_isbn) ISBN : {{ $livre->numero_isbn }} @endif
                                        @if ($livre->anneeEdition) · {{ $livre->anneeEdition->libelle }} @endif
                                    </span>
                                </a>
                            </td>
                            <td class="text-sm">{{ $livre->auteur?->nom_prenom ?? '—' }}</td>
                            <td class="text-sm">{{ $livre->categorie?->libelle ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge-dispo {{ $badge }}">
                                    <span class="badge-dot"></span>{{ $label }} ({{ $dispo }}/{{ $total }})
                                </span>
                            </td>
                            <td class="text-sm">
                                @php $pret = $livre->pretEnCours(); @endphp
                                @if ($pret)
                                    <div class="fw-semibold">
                                        {{ trim(($pret->inscription?->eleve?->nom ?? '') . ' ' . ($pret->inscription?->eleve?->prenom ?? '')) }}
                                    </div>
                                    <div class="text-xs text-muted">
                                        Retour prévu : {{ optional($pret->date_retour_pret)->format('d/m/Y') }}
                                        @if ($pret->isEnRetard())
                                            <span style="color:var(--school-red);font-weight:700;">
                                                · {{ $pret->jours_retard }} j de retard
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('services-eleves.bibliotheque.livres.show', $livre) }}">
                                                <i class="fas fa-eye"></i> Voir la fiche
                                            </a>
                                        </li>
                                        @can('bibliotheque.preter')
                                            @if ($livre->nombre_disponibles > 0)
                                                <li>
                                                    <button class="dropdown-item btn-pret-livre"
                                                            data-livre-id="{{ $livre->id }}"
                                                            data-livre-titre="{{ $livre->titre }}"
                                                            data-bs-toggle="modal" data-bs-target="#pretModal">
                                                        <i class="fas fa-hand-holding"></i> Prêter
                                                    </button>
                                                </li>
                                            @endif
                                        @endcan
                                        @can('bibliotheque.gerer')
                                            <li>
                                                <button class="dropdown-item btn-edit-livre"
                                                        data-id="{{ $livre->id }}"
                                                        data-titre="{{ $livre->titre }}"
                                                        data-numero="{{ $livre->numero }}"
                                                        data-isbn="{{ $livre->numero_isbn }}"
                                                        data-auteur="{{ $livre->auteur_id }}"
                                                        data-maison="{{ $livre->maison_edition_id }}"
                                                        data-annee="{{ $livre->annee_edition_id }}"
                                                        data-categorie="{{ $livre->categorie_livre_id }}"
                                                        data-exemplaires="{{ $livre->nombre_exemplaires }}"
                                                        data-description="{{ $livre->description }}"
                                                        data-bs-toggle="modal" data-bs-target="#livreModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('services-eleves.bibliotheque.livres.destroy', $livre) }}" method="POST" class="form-confirm-delete">
                                                    @csrf @method('DELETE')
                                                    <button class="dropdown-item text-danger"><i class="fas fa-trash"></i> Désactiver</button>
                                                </form>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><i class="fas fa-book"></i><p>Aucun livre au catalogue.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $livres->appends(request()->query())->links('vendor.pagination.custom') }}</div>
</div>

{{-- Modal livre --}}
@can('bibliotheque.gerer')
<div class="modal fade" id="livreModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-livres">
            <form id="livre-form">
                @csrf
                <input type="hidden" name="_method" id="livre-method" value="POST">
                <div class="modal-header modal-header-livres">
                    <h5 class="modal-title" id="livre-title"><i class="fas fa-book me-2"></i>Nouveau livre</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-section mb-3">
                        <div class="form-section-title"><i class="fas fa-info-circle"></i> Identité de l'ouvrage</div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" name="titre" id="l-titre" class="form-control" maxlength="200" required>
                                <div class="text-danger text-xs mt-1" id="titre-error"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nombre d'exemplaires <span class="text-danger">*</span></label>
                                <input type="number" name="nombre_exemplaires" id="l-exemplaires" class="form-control" min="1" max="999" value="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° interne</label>
                                <input type="text" name="numero" id="l-numero" class="form-control" maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° ISBN</label>
                                <input type="text" name="numero_isbn" id="l-isbn" class="form-control" maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Auteur</label>
                                <select name="auteur_id" id="l-auteur" class="form-select">
                                    <option value="">— Aucun —</option>
                                    @foreach ($auteurs as $a)
                                        <option value="{{ $a->id }}">{{ $a->nom_prenom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Catégorie</label>
                                <select name="categorie_livre_id" id="l-categorie" class="form-select">
                                    <option value="">— Aucune —</option>
                                    @foreach ($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Maison d'édition</label>
                                <select name="maison_edition_id" id="l-maison" class="form-select">
                                    <option value="">— Aucune —</option>
                                    @foreach ($maisons as $m)
                                        <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Année d'édition</label>
                                <select name="annee_edition_id" id="l-annee" class="form-select">
                                    <option value="">— Aucune —</option>
                                    {{-- à itérer sur les années d'édition --}}
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="l-description" rows="2" class="form-control" maxlength="2000"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Modal prêt --}}
@can('bibliotheque.preter')
<div class="modal fade" id="pretModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-livres">
            <form id="pret-form">
                @csrf
                <input type="hidden" name="livre_id" id="pret-livre-id">
                <div class="modal-header modal-header-livres">
                    <h5 class="modal-title"><i class="fas fa-hand-holding me-2"></i>Prêter un livre</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border-0 small mb-3" style="background:var(--b-bg-sold,#faf6ee);color:var(--school-muted);">
                        <strong>Livre :</strong> <span id="pret-livre-titre">—</span>
                    </div>
                    <div class="form-section">
                        <div class="mb-3">
                            <label class="form-label">Emprunteur (élève) <span class="text-danger">*</span></label>
                            @include('admin.partials.select-eleve')
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date du prêt <span class="text-danger">*</span></label>
                                <input type="date" name="date_pret" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Retour prévu <span class="text-danger">*</span></label>
                                <input type="date" name="date_retour_pret" class="form-control" value="{{ now()->addDays(14)->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Caution</label>
                                <input type="number" name="caution" step="0.01" min="0" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observations</label>
                                <textarea name="observations" rows="2" class="form-control" maxlength="2000"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Prêter</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('js')
<script>
$(function () {
    // ============ FORMULAIRE LIVRE ============
    const $livreForm = $('#livre-form');
    if ($livreForm.length) {
        let currentId = null;
        $('#btnNouveauLivre').on('click', function () {
            currentId = null;
            $livreForm[0].reset();
            $('#livre-method').val('POST');
            $('#livre-title').html('<i class="fas fa-book me-2"></i>Nouveau livre');
            $('#l-exemplaires').val(1);
        });

        $(document).on('click', '.btn-edit-livre', function () {
            currentId = $(this).data('id');
            $('#l-titre').val($(this).data('titre'));
            $('#l-numero').val($(this).data('numero'));
            $('#l-isbn').val($(this).data('isbn'));
            $('#l-auteur').val($(this).data('auteur') ?? '');
            $('#l-maison').val($(this).data('maison') ?? '');
            $('#l-categorie').val($(this).data('categorie') ?? '');
            $('#l-exemplaires').val($(this).data('exemplaires'));
            $('#l-description').val($(this).data('description'));
            $('#livre-method').val('PUT');
            $('#livre-title').html('<i class="fas fa-pen me-2"></i>Modifier le livre');
        });

        $livreForm.on('submit', function (e) {
            e.preventDefault();
            const url = currentId
                ? `{{ url('services-eleves/bibliotheque/livres') }}/${currentId}`
                : `{{ route('services-eleves.bibliotheque.livres.store') }}`;
            $.ajax({
                url, method: 'POST', data: $livreForm.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#livreModal').modal('hide');
                    window.showToastThenReload(res.message || 'Enregistré.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                    } else {
                        window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                    }
                }
            });
        });
    }

    // ============ FORMULAIRE PRÊT ============
    $('.btn-pret-livre').on('click', function () {
        $('#pret-livre-id').val($(this).data('livre-id'));
        $('#pret-livre-titre').text($(this).data('livre-titre'));
    });

    $('#pret-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.bibliotheque.prets.store") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#pretModal').modal('hide');
                window.showToastThenReload(res.message || 'Prêt enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            }
        });
    });
});
</script>
@endpush