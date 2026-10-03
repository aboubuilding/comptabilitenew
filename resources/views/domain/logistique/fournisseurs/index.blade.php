@extends('admin.layouts.app')

@section('title', 'Fournisseurs')
@section('page_icon', 'fa-truck')
@section('page_title', 'Fournisseurs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Fournisseurs</li>
@endsection

@section('page_actions')
    <a href="{{ route('logistique.fournisseurs.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Excel
    </a>
    <a href="{{ route('logistique.fournisseurs.exportPdf', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-pdf me-1"></i> PDF
    </a>
    @can('fournisseurs.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouveauFournisseur"
                data-bs-toggle="modal" data-bs-target="#fournisseurModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau fournisseur
        </button>
    @endcan
@endsection

@section('css')
<style>
    .fourn-page { --fr-bg-soft:#faf6ee; --fr-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--fr-border); border-radius:12px; padding:1.05rem 1.15rem; display:flex; align-items:center; gap:.9rem; transition:transform .2s; box-shadow:0 2px 8px rgba(122,15,28,.04); }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--fr-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--fr-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background:var(--school-red-dark); color:#fff; }
    .compteur-badge { display:inline-block; background:var(--fr-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--fr-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--fr-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--fr-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-fourn thead tr { background:var(--fr-bg-soft); }
    .table-fourn thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--fr-border); white-space:nowrap; }
    .table-fourn tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--fr-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-fourn tbody tr:hover { background:#fdfaf5; }

    .raison-sociale { font-weight:700; color:var(--school-ink); display:block; }
    .contact-sub { font-size:.75rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }
    .badge-count { display:inline-flex; align-items:center; justify-content:center; background:var(--fr-bg-soft); color:var(--school-red); font-weight:700; font-size:.75rem; padding:.35em .8em; border-radius:999px; min-width:36px; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-fourn { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-fourn { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--fr-bg-soft); border:1px solid var(--fr-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control { border-radius:8px; border:1px solid var(--fr-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--fr-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="fourn-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-truck"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $fournisseurs->total() }}</div>
                    <div class="stat-label">Fournisseurs actifs</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $fournisseurs->sum('nombre_achats') }}</div>
                    <div class="stat-label">Achats cumulés</div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6 text-md-end d-flex align-items-end justify-content-md-end">
            <span class="compteur-badge">{{ $fournisseurs->total() }} fiche(s)</span>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Raison sociale, contact, téléphone, email..."
                               value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('logistique.fournisseurs.index') }}" class="btn btn-outline-scolarite w-100">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-fourn align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Raison sociale</th>
                            <th>Contact</th>
                            <th>Téléphone / Email</th>
                            <th class="text-center">Achats</th>
                            <th class="text-end">Montant total</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($fournisseurs as $f)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('logistique.fournisseurs.show', $f) }}" class="text-decoration-none">
                                    <span class="raison-sociale">{{ $f->raison_social }}</span>
                                </a>
                            </td>
                            <td class="text-sm">
                                {{ $f->nom_contact ?? '—' }}
                                @if ($f->adresse)
                                    <div class="contact-sub"><i class="fas fa-map-marker-alt me-1"></i>{{ \Str::limit($f->adresse, 40) }}</div>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($f->telephone_contact)
                                    <div><i class="fas fa-phone me-1 text-muted"></i>{{ $f->telephone_contact }}</div>
                                @endif
                                @if ($f->email_contact)
                                    <div><i class="fas fa-envelope me-1 text-muted"></i>{{ $f->email_contact }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-count">{{ $f->nombre_achats }}</span>
                            </td>
                            <td class="num grand">
                                {{ number_format($f->montant_total_achats, 2, ',', ' ') }}
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logistique.fournisseurs.show', $f) }}">
                                                <i class="fas fa-eye"></i> Voir le détail
                                            </a>
                                        </li>
                                        @can('fournisseurs.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-fourn"
                                                    data-id="{{ $f->id }}"
                                                    data-raison="{{ $f->raison_social }}"
                                                    data-contact="{{ $f->nom_contact }}"
                                                    data-telephone="{{ $f->telephone_contact }}"
                                                    data-email="{{ $f->email_contact }}"
                                                    data-adresse="{{ $f->adresse }}"
                                                    data-nif="{{ $f->nif }}"
                                                    data-rccm="{{ $f->rccm }}"
                                                    data-bs-toggle="modal" data-bs-target="#fournisseurModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('fournisseurs.gerer')
                                        <li>
                                            <form action="{{ route('logistique.fournisseurs.destroy', $f) }}" method="POST" class="form-confirm-delete"
                                                  data-confirm-title="Désactiver ce fournisseur ?">
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
                        <tr><td colspan="6"><div class="empty-state"><i class="fas fa-truck"></i><p>Aucun fournisseur.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $fournisseurs->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modale création / édition --}}
    @can('fournisseurs.gerer')
    <div class="modal fade" id="fournisseurModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-fourn">
                <form id="fournisseur-form">
                    @csrf
                    <input type="hidden" name="_method" id="fournisseur-method" value="POST">
                    <div class="modal-header modal-header-fourn">
                        <h5 class="modal-title" id="fournisseur-title"><i class="fas fa-truck me-2"></i>Nouveau fournisseur</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Identité</div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Raison sociale <span class="text-danger">*</span></label>
                                    <input type="text" name="raison_social" id="f-raison" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="raison_social-error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nom du contact</label>
                                    <input type="text" name="nom_contact" id="f-contact" class="form-control" maxlength="150">
                                </div>
                            </div>
                        </div>

                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-address-book"></i> Coordonnées</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input type="text" name="telephone_contact" id="f-telephone" class="form-control" maxlength="30">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email_contact" id="f-email" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="email_contact-error"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Adresse</label>
                                    <textarea name="adresse" id="f-adresse" rows="2" class="form-control" maxlength="1000"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-file-invoice"></i> Informations fiscales (facultatif)</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">NIF</label>
                                    <input type="text" name="nif" id="f-nif" class="form-control" maxlength="50">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">RCCM</label>
                                    <input type="text" name="rccm" id="f-rccm" class="form-control" maxlength="50">
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
</div>
@endsection

@push('js')
<script>
$(function () {
    const $form = $('#fournisseur-form');
    if (!$form.length) return;

    let currentId = null;

    $('#btnNouveauFournisseur').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#fournisseur-method').val('POST');
        $('#fournisseur-title').html('<i class="fas fa-truck me-2"></i>Nouveau fournisseur');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-fourn', function () {
        currentId = $(this).data('id');
        $('#f-raison').val($(this).data('raison'));
        $('#f-contact').val($(this).data('contact'));
        $('#f-telephone').val($(this).data('telephone'));
        $('#f-email').val($(this).data('email'));
        $('#f-adresse').val($(this).data('adresse'));
        $('#f-nif').val($(this).data('nif'));
        $('#f-rccm').val($(this).data('rccm'));
        $('#fournisseur-method').val('PUT');
        $('#fournisseur-title').html('<i class="fas fa-pen me-2"></i>Modifier le fournisseur');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('logistique/fournisseurs') }}/${currentId}`
            : `{{ route('logistique.fournisseurs.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#fournisseurModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    function clearErrors() { $form.find('[id$="-error"]').text(''); }
});
</script>
@endpush