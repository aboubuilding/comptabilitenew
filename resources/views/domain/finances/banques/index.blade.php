{{-- resources/views/domain/finances/banques/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Comptes bancaires')
@section('page_icon', 'fa-university')
@section('page_title', 'Comptes bancaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Banques</li>
@endsection

@section('page_actions')
    @can('banques.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelleBanque" data-bs-toggle="modal" data-bs-target="#banqueModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau compte bancaire
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .banques-page { --banques-bg-soft: var(--eim-cream); --banques-border: var(--eim-border); }
    .text-xs { font-size: .72rem; }
    .text-sm { font-size: .8rem; }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--banques-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--banques-border); padding:.55rem .9rem; font-size:.88rem; }


    .btn-filtrer { background: var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }

    .compteur-badge { display:inline-block; background: var(--banques-bg-soft); color: var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--banques-border); }

    .btn-nouveau:hover { color:#fff; transform: translateY(-2px); }

    .table-banques thead tr { background: var(--banques-bg-soft); }
    .table-banques thead th { color: var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--banques-border); padding:.9rem 1rem; }
    .table-banques thead th i { color: var(--school-gold-dark); }
    .table-banques tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--banques-border); font-size:.9rem; color: var(--school-ink); vertical-align:middle; }
    .table-banques tbody tr:hover { background:var(--eim-hover); }

    .banque-nom { font-weight:700; color: var(--school-ink); display:block; }
    .banque-id { font-size:.72rem; color: var(--school-muted); }

    .empty-state { padding: 3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .empty-state p { font-size:.9rem; }

    .modal-banques { border:none; border-radius:16px; overflow:hidden; box-shadow: 0 20px 40px rgba(0,0,0,.15); }
    .modal-header-banques { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .modal-header-banques .modal-title { font-weight:600; font-size:1.05rem; }

    .form-section { background: var(--banques-bg-soft); border:1px solid var(--banques-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color: var(--school-red); margin-bottom:.75rem; display:flex; align-items:center; gap:6px; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color: var(--school-ink); }
    .form-section .form-control { border-radius:8px; border:1px solid var(--banques-border); padding:.55rem .75rem; font-size:.88rem; }

    .btn-annuler { background:#fff; border:1px solid var(--banques-border); color:var(--eim-ink-soft); border-radius:8px; font-weight:500; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-annuler:hover { background: var(--banques-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
    .btn-enregistrer:hover { color:#fff; }
</style>
@endsection

@section('contenu')
<div class="banques-page">

    {{-- Tableau principal --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <x-recherche name="search" placeholder="Rechercher une banque..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3 col-6">
                    <x-bouton variante="filtrer" type="submit" class="w-100">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </x-bouton>
                </div>
                <div class="col-md-3 col-6 text-end">
                    <x-compteur>{{ $banques->total() }} banque(s)</x-compteur>
                    <div class="text-muted small mt-1" id="annee-cheques"><i class="fas fa-calendar-alt me-1"></i>Chèques de l'année {{ \App\Domain\Scolarite\Models\Annee::find($filters['annee_id'] ?? null)?->libelle ?? '—' }}</div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-banques align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-university me-2"></i>Banque</th>
                            <th class="text-center"><i class="fas fa-money-check me-2"></i>Chèques</th>
                            <th class="text-end"><i class="fas fa-check-circle me-2"></i>Réellement perçu</th>
                            <th class="text-end"><i class="fas fa-hourglass-half me-2"></i>Reste à percevoir</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($banques as $banque)
                            <tr>
                                <td class="ps-4">
                                    <span class="banque-nom">{{ $banque->nom }}</span>
                                    <span class="banque-id">#{{ $banque->id }}</span>
                                </td>
                                <td class="text-center">
                                    <x-compteur>{{ $banque->cheques_total ?? 0 }}</x-compteur>
                                    <div class="text-muted small mt-1">{{ $banque->cheques_en_attente ?? 0 }} en attente</div>
                                </td>
                                @foreach (['montant-percu' => [$banque->montant_percu, '#1F7A4D'], 'montant-reste' => [$banque->montant_reste, 'var(--school-red)']] as $classe => [$montant, $couleur])
                                    <td class="text-end {{ $classe }}" style="font-variant-numeric: tabular-nums; font-weight:600; color:{{ (float) $montant > 0 ? $couleur : 'var(--school-muted)' }};">
                                        {{ (float) $montant > 0 ? number_format((float) $montant, 2, ',', ' ') : '—' }}
                                    </td>
                                @endforeach
                                <td class="text-center pe-4">
                                    <x-actions>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('finances.banques.show', ['banque' => $banque->id, 'onglet' => 'attente']) }}">
                                                    <i class="fas fa-money-check"></i> Paiements et chèques
                                                </a>
                                            </li>
                                            @can('banques.gerer')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-banque"
                                                        data-id="{{ $banque->id }}"
                                                        data-nom="{{ $banque->nom }}"
                                                        data-bs-toggle="modal" data-bs-target="#banqueModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            @endcan
                                            @if (($banque->cheques_total ?? 0) === 0)
                                                <li><hr class="dropdown-divider"></li>
                                                @can('banques.gerer')
                                                <x-actions.supprimer :action="route('finances.banques.destroy', $banque)" data-confirm-title="Désactiver cette banque ?" data-confirm-text="Elle n'apparaîtra plus dans les listes." />
                                                @endcan
                                            @endif
                                        </x-actions>
                                </td>
                            </tr>
                        @empty
                            <x-vide colspan="5" icone="fas fa-university" :paragraphe="false"><p class="mb-0 fw-medium">Aucun compte bancaire enregistré.</p></x-vide>
                        @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4">
        <x-pagination :liste="$banques->appends(request()->query())" />
    </div>

    {{-- Modale création / édition --}}
    @can('banques.gerer')
    <x-modale id="banqueModal" data-bs-backdrop="static" aria-hidden="true" contenu-class="modal-banques" entete-class="modal-header-banques" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="banque-form">@csrf
                    <input type="hidden" name="_method" id="banque-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="banque-modal-title"><i class="fas fa-university me-2"></i>Nouveau compte bancaire</x-slot:titre>
        <x-section titre="Informations" icone="fas fa-info-circle">
            <x-libelle requis>Nom de la banque</x-libelle>
            <input type="text" name="nom" id="banque-nom" class="form-control"
                   maxlength="150" placeholder="Ex : Ecobank, BOA, UTB...">
            <div class="text-danger text-xs mt-1" id="nom-error"></div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">
                <i class="fas fa-save me-1"></i> Enregistrer
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    const $form   = $('#banque-form');
    const $title  = $('#banque-modal-title');
    const $method = $('#banque-method');
    let currentId = null;

    $('#btnNouvelleBanque').on('click', function () {
        currentId = null;
        $form[0].reset();
        $method.val('POST');
        $title.html('<i class="fas fa-university me-2"></i>Nouveau compte bancaire');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-banque', function () {
        currentId = $(this).data('id');
        $('#banque-nom').val($(this).data('nom'));
        $method.val('PUT');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier le compte bancaire');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('finances/banques') }}/${currentId}`
            : `{{ route('finances.banques.store') }}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#banqueModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(function (f) {
                        $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]);
                    });
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    function clearErrors() {
        $form.find('[id$="-error"]').text('');
    }
});
</script>
@endpush