@extends('admin.layouts.app')

@section('title', 'Prévisions budgétaires')
@section('page_icon', 'fa-chart-line')
@section('page_title', 'Prévisions budgétaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Prévisions budgétaires</li>
@endsection

@section('page_actions')
    @can('previsions.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvellePrevision"
                data-bs-toggle="modal" data-bs-target="#previsionModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle prévision
        </button>
    @endcan
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid #ece4d6 !important; }
    .filtre-input { border-radius:10px; border:1px solid #ece4d6; padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .table-prev thead tr { background:#faf6ee; }
    .table-prev thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid #ece4d6; white-space:nowrap; }
    .table-prev tbody td { padding:.9rem 1rem; border-bottom:1px solid #ece4d6; font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-prev tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.positif { color:#1c7a4d; }
    .num.negatif { color:var(--school-red); }
    .badge-type { display:inline-flex; padding:.3em .8em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-type.recette { background:#e3f7ec; color:#1c7a4d; }
    .badge-type.depense { background:#fdecea; color:var(--school-red); }
    .centre-code { font-family:monospace; background:#faf6ee; color:var(--school-red); padding:2px 8px; border-radius:6px; font-size:.75rem; font-weight:700; }
    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .modal-prev { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-prev { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:#faf6ee; border:1px solid #ece4d6; border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid #ece4d6; padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid #ece4d6; color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div>
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control filtre-input"
                           placeholder="Rechercher..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="centre_analytique_id" class="form-select filtre-input">
                        <option value="">— Centre analytique —</option>
                        @foreach ($centres as $c)
                            <option value="{{ $c->id }}" @selected(($filters['centre_analytique_id'] ?? '') == $c->id)>
                                {{ $c->code }} — {{ $c->libelle }}
                            </option>
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-prev align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Libellé</th>
                            <th>Type</th>
                            <th>Centre</th>
                            <th>Période</th>
                            <th class="text-end">Prévisionnel</th>
                            <th class="text-end">Réalisé</th>
                            <th class="text-end">Écart</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($previsions as $p)
                        @php $ecart = $p->ecart(); @endphp
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $p->libelle }}</td>
                            <td><span class="badge-type {{ $p->type?->css() }}">{{ $p->type?->label() }}</span></td>
                            <td>
                                @if ($p->centre)
                                    <span class="centre-code">{{ $p->centre->code }}</span>
                                    <div class="text-xs text-muted mt-1">{{ $p->centre->libelle }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($p->date_debut && $p->date_fin)
                                    {{ $p->date_debut->format('d/m/Y') }} → {{ $p->date_fin->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="num">{{ number_format($p->montant_previsionnel, 2, ',', ' ') }}</td>
                            <td class="num">{{ number_format($p->montant_realise ?? 0, 2, ',', ' ') }}</td>
                            <td class="num {{ $ecart >= 0 ? 'positif' : 'negatif' }}">
                                {{ $ecart >= 0 ? '+' : '' }}{{ number_format($ecart, 2, ',', ' ') }}
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('previsions.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-prev"
                                                    data-id="{{ $p->id }}"
                                                    data-libelle="{{ $p->libelle }}"
                                                    data-type="{{ $p->type?->value }}"
                                                    data-montant="{{ $p->montant_previsionnel }}"
                                                    data-centre="{{ $p->centre_analytique_id }}"
                                                    data-debut="{{ optional($p->date_debut)->toDateString() }}"
                                                    data-fin="{{ optional($p->date_fin)->toDateString() }}"
                                                    data-bs-toggle="modal" data-bs-target="#previsionModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('previsions.gerer')
                                        <li>
                                            <form action="{{ route('finances.previsions.destroy', $p) }}" method="POST" class="form-confirm-delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Supprimer</button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-chart-line"></i><p>Aucune prévision.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $previsions->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('previsions.gerer')
    <div class="modal fade" id="previsionModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-prev">
                <form id="prev-form">
                    @csrf
                    <input type="hidden" name="_method" id="prev-method" value="POST">
                    <div class="modal-header modal-header-prev">
                        <h5 class="modal-title" id="prev-title"><i class="fas fa-chart-line me-2"></i>Nouvelle prévision</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Détail de la prévision</div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="prev-libelle" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="prev-type" class="form-select">
                                        @foreach ($types as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Montant prévisionnel <span class="text-danger">*</span></label>
                                    <input type="number" name="montant_previsionnel" id="prev-montant" step="0.01" min="0.01" class="form-control">
                                    <div class="text-danger text-xs mt-1" id="montant_previsionnel-error"></div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Centre analytique</label>
                                    <select name="centre_analytique_id" id="prev-centre" class="form-select">
                                        <option value="">— Aucun (prévision globale) —</option>
                                        @foreach ($centres as $c)
                                            <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date de début</label>
                                    <input type="date" name="date_debut" id="prev-debut" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date de fin</label>
                                    <input type="date" name="date_fin" id="prev-fin" class="form-control">
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
    const $form = $('#prev-form');
    if (!$form.length) return;

    let currentId = null;

    $('#btnNouvellePrevision').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#prev-method').val('POST');
        $('#prev-title').html('<i class="fas fa-chart-line me-2"></i>Nouvelle prévision');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-prev', function () {
        currentId = $(this).data('id');
        $('#prev-libelle').val($(this).data('libelle'));
        $('#prev-type').val($(this).data('type'));
        $('#prev-montant').val($(this).data('montant'));
        $('#prev-centre').val($(this).data('centre') ?? '');
        $('#prev-debut').val($(this).data('debut') ?? '');
        $('#prev-fin').val($(this).data('fin') ?? '');
        $('#prev-method').val('PUT');
        $('#prev-title').html('<i class="fas fa-pen me-2"></i>Modifier la prévision');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('finances/previsions') }}/${currentId}`
            : `{{ route('finances.previsions.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#previsionModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                } else { window.showToast("Une erreur est survenue.", 'error'); }
            }
        });
    });

    function clearErrors() { $form.find('[id$="-error"]').text(''); }
});
</script>
@endpush