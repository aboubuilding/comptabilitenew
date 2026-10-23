@extends('admin.layouts.app')

@section('title', 'Règles de ventilation')
@section('page_icon', 'fa-project-diagram')
@section('page_title', 'Règles de ventilation automatique')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item"><a href="{{ route('finances.analytique.centres.index') }}">Centres analytiques</a></li>
    <li class="breadcrumb-item active">Règles de ventilation</li>
@endsection

@section('page_actions')
    @can('analytique.gerer')
    <x-bouton variante="nouveau" type="button" id="btnNouvelleRegle" data-bs-toggle="modal" data-bs-target="#regleModal">
        <i class="fas fa-plus-circle me-1"></i> Nouvelle règle
    </x-bouton>
    @endcan
@endsection

@section('contenu')
<div>
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="nature" class="form-select filtre-input">
                        <option value="">— Nature —</option>
                        @foreach ($natures as $n)
                            <option value="{{ $n }}" @selected(($filters['nature'] ?? '') === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="sens" class="form-select filtre-input">
                        <option value="">— Sens —</option>
                        @foreach ($sens as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['sens'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
                <div class="col-md-4 text-end">
                    <x-compteur>{{ $regles->total() }} règle(s)</x-compteur>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="align-middle mb-0">
                    <thead style="background:var(--eim-cream);">
                        <tr>
                            <th class="ps-4" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Nature</th>
                            <th style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Sens</th>
                            <th style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Centre cible</th>
                            <th class="text-end" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Quote-part</th>
                            <th class="text-end" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Priorité</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($regles as $regle)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $regle->nature }}</td>
                            <td>
                                <span class="badge-type {{ $regle->sens?->value }}"
                                      style="padding:.3em .8em;border-radius:999px;font-size:.72rem;
                                             background:{{ $regle->sens?->value === 'recette' ? '#e3f7ec' : '#fdecea' }};
                                             color:{{ $regle->sens?->value === 'recette' ? '#1F7A4D' : 'var(--school-red)' }};">
                                    {{ $regle->sens?->label() }}
                                </span>
                            </td>
                            <td>
                                <span class="centre-code">{{ $regle->centre?->code }}</span>
                                <span class="ms-2">{{ $regle->centre?->libelle }}</span>
                            </td>
                            <td class="num">{{ number_format($regle->pourcentage, 2, ',', ' ') }} %</td>
                            <td class="num">{{ $regle->priorite }}</td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('analytique.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-regle"
                                                    data-id="{{ $regle->id }}"
                                                    data-nature="{{ $regle->nature }}"
                                                    data-sens="{{ $regle->sens?->value }}"
                                                    data-centre="{{ $regle->centre_analytique_id }}"
                                                    data-pourcentage="{{ $regle->pourcentage }}"
                                                    data-priorite="{{ $regle->priorite }}"
                                                    data-bs-toggle="modal" data-bs-target="#regleModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('analytique.gerer')
                                        <x-actions.supprimer :action="route('finances.regles.destroy', $regle)">Supprimer</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="6" icone="fas fa-project-diagram">Aucune règle définie.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$regles->appends(request()->query())" /></div>

    @can('analytique.gerer')
    <x-modale id="regleModal" data-bs-backdrop="static" contenu-class="modal-prev" entete-class="modal-header-prev" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="regle-form">@csrf
                    <input type="hidden" name="_method" id="regle-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="regle-title"><i class="fas fa-project-diagram me-2"></i>Nouvelle règle</x-slot:titre>
        <x-section>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Nature</x-libelle>
                    <input type="text" name="nature" id="regle-nature" class="form-control"
                           placeholder="ex : scolarite, cantine, bus, fournitures...">
                    <div class="text-danger text-xs mt-1" id="nature-error"></div>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Sens</x-libelle>
                    <select name="sens" id="regle-sens" class="form-select">
                        @foreach ($sens as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <x-libelle requis>Centre cible</x-libelle>
                    <select name="centre_analytique_id" id="regle-centre" class="form-select">
                        <option value="">— Sélectionner —</option>
                        @foreach ($centres as $c)
                            <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Quote-part (%)</x-libelle>
                    <input type="number" name="pourcentage" id="regle-pourcentage" step="0.01" min="0.01" max="100" class="form-control">
                </div>
                <div class="col-md-4">
                    <x-libelle>Priorité</x-libelle>
                    <input type="number" name="priorite" id="regle-priorite" min="0" value="0" class="form-control">
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
    const $form = $('#regle-form');
    let currentId = null;

    $('#btnNouvelleRegle').on('click', function () {
        currentId = null; $form[0].reset(); $('#regle-method').val('POST');
        $('#regle-title').html('<i class="fas fa-project-diagram me-2"></i>Nouvelle règle');
    });

    $(document).on('click', '.btn-edit-regle', function () {
        currentId = $(this).data('id');
        $('#regle-nature').val($(this).data('nature'));
        $('#regle-sens').val($(this).data('sens'));
        $('#regle-centre').val($(this).data('centre'));
        $('#regle-pourcentage').val($(this).data('pourcentage'));
        $('#regle-priorite').val($(this).data('priorite'));
        $('#regle-method').val('PUT');
        $('#regle-title').html('<i class="fas fa-pen me-2"></i>Modifier la règle');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('finances/regles') }}/${currentId}`
            : `{{ route('finances.regles.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#regleModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                } else { window.showToast("Une erreur est survenue.", 'error'); }
            }
        });
    });
});
</script>
@endpush