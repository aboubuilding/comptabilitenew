@extends('admin.layouts.app')

@section('title', 'Centres analytiques')
@section('page_icon', 'fa-sitemap')
@section('page_title', 'Centres analytiques')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Centres analytiques</li>
@endsection

@section('page_actions')
    @can('analytique.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauCentre" data-bs-toggle="modal" data-bs-target="#centreModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau centre
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .centres-page { --cp-bg-soft:var(--eim-cream); --cp-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--cp-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--cp-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--cp-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--cp-border); }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .table-centres thead tr { background:var(--cp-bg-soft); }
    .table-centres thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--cp-border); }
    .table-centres thead th i { color:var(--school-gold-dark); }
    .table-centres tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--cp-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-centres tbody tr:hover { background:var(--eim-hover); }
    .centre-code { font-family:monospace; background:var(--cp-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-size:.75rem; font-weight:700; }
    .centre-libelle { font-weight:700; }
    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .modal-centres { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-centres { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--cp-bg-soft); border:1px solid var(--cp-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--cp-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--cp-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="centres-page">

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <x-recherche name="search" placeholder="Code ou libellé..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
                <div class="col-md-2 text-end">
                    <x-compteur>{{ $centres->total() }} centre(s)</x-compteur>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-centres align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Code</th>
                            <th>Libellé</th>
                            <th>Type</th>
                            <th>Responsable</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($centres as $centre)
                        <tr>
                            <td class="ps-4"><span class="centre-code">{{ $centre->code }}</span></td>
                            <td><span class="centre-libelle">{{ $centre->libelle }}</span>
                                @if ($centre->description)<div class="text-xs text-muted">{{ \Str::limit($centre->description, 80) }}</div>@endif
                            </td>
                            <td><x-compteur>{{ $centre->type?->label() }}</x-compteur></td>
                            <td class="text-sm">{{ $centre->responsable?->name ?? '—' }}</td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('analytique.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-centre"
                                                    data-id="{{ $centre->id }}"
                                                    data-code="{{ $centre->code }}"
                                                    data-libelle="{{ $centre->libelle }}"
                                                    data-description="{{ $centre->description }}"
                                                    data-type="{{ $centre->type?->value }}"
                                                    data-responsable="{{ $centre->responsable_id }}"
                                                    data-bs-toggle="modal" data-bs-target="#centreModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        @if ($centre->imputations()->count() === 0)
                                            <li><hr class="dropdown-divider"></li>
                                            @can('analytique.gerer')
                                            <x-actions.supprimer :action="route('finances.analytique.centres.destroy', $centre)" data-confirm-title="Désactiver ce centre ?" />
                                            @endcan
                                        @endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-sitemap">Aucun centre analytique configuré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$centres->appends(request()->query())" /></div>

    {{-- Modal création / édition --}}
    @can('analytique.gerer')
    <x-modale id="centreModal" data-bs-backdrop="static" aria-hidden="true" contenu-class="modal-centres" entete-class="modal-header-centres" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="centre-form">@csrf
                    <input type="hidden" name="_method" id="centre-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="centre-modal-title"><i class="fas fa-sitemap me-2"></i>Nouveau centre analytique</x-slot:titre>
        <x-section titre="Identification" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle requis>Code</x-libelle>
                    <input type="text" name="code" id="centre-code" class="form-control" maxlength="30" placeholder="SCOL">
                    <div class="text-danger text-xs mt-1" id="code-error"></div>
                </div>
                <div class="col-md-8">
                    <x-libelle requis>Libellé</x-libelle>
                    <input type="text" name="libelle" id="centre-libelle" class="form-control" maxlength="150">
                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Type</x-libelle>
                    <select name="type" id="centre-type" class="form-select">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Responsable</x-libelle>
                    <select name="responsable_id" id="centre-responsable" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <x-libelle>Description</x-libelle>
                    <textarea name="description" id="centre-description" rows="2" class="form-control" maxlength="1000"></textarea>
                </div>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    const $form = $('#centre-form');
    if (!$form.length) return;

    let currentId = null;

    $('#btnNouveauCentre').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#centre-method').val('POST');
        $('#centre-modal-title').html('<i class="fas fa-sitemap me-2"></i>Nouveau centre analytique');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-centre', function () {
        currentId = $(this).data('id');
        $('#centre-code').val($(this).data('code'));
        $('#centre-libelle').val($(this).data('libelle'));
        $('#centre-type').val($(this).data('type'));
        $('#centre-responsable').val($(this).data('responsable') ?? '');
        $('#centre-description').val($(this).data('description') ?? '');
        $('#centre-method').val('PUT');
        $('#centre-modal-title').html('<i class="fas fa-pen me-2"></i>Modifier le centre');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('finances/analytique/centres') }}/${currentId}`
            : `{{ route('finances.analytique.centres.store') }}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#centreModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => {
                        $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]);
                    });
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