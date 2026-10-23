@extends('admin.layouts.app')

@section('page_title', 'Assurances du personnel')
@section('page_icon', 'fa-shield-halved')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('rh.assures.index') }}">Ressources Humaines</a></li>
    <li class="breadcrumb-item active">Assurances</li>
@endsection

@section('page_actions')
    @can('assurances.gerer')
    <button type="button" class="btn-nouveau" id="btn-nouvelle-assurance">
        <i class="fa fa-plus"></i> Nouvelle souscription
    </button>
    @endcan
    <a href="{{ route('rh.assures.export', request()->query()) }}" class="btn-outline-scolarite ms-2">
        <i class="fa fa-file-excel"></i> Exporter
    </a>
@endsection

@section('css')
<style>
    .stat-card { border-left: 4px solid var(--school-red); }
</style>
@endsection

@section('contenu')

    {{-- Cartes statistiques --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="stat-card p-3 bg-white rounded shadow-sm">
                <div class="text-muted small">Total assurés</div>
                <div class="h4 mb-0">{{ $stats['total_assures'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card p-3 bg-white rounded shadow-sm">
                <div class="text-muted small">Assurés actifs</div>
                <div class="h4 mb-0">{{ $stats['total_actifs'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card p-3 bg-white rounded shadow-sm">
                <div class="text-muted small">Total prélèvements mensuels</div>
                <div class="h4 mb-0">{{ number_format($stats['total_prelevements'], 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="main-card p-3 mb-3">
        <form method="GET" action="{{ route('rh.assures.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <x-libelle class="small">Employé</x-libelle>
                <select name="employe_id" class="form-select filtre-input">
                    <option value="">Tous</option>
                    @foreach($employes as $employe)
                        <option value="{{ $employe->id }}" @selected(($filtres['employe_id'] ?? '') == $employe->id)>
                            {{ $employe->nom_prenom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <x-libelle class="small">Assurance</x-libelle>
                <select name="assurance_id" class="form-select filtre-input">
                    <option value="">Toutes</option>
                    @foreach($assurances as $assurance)
                        <option value="{{ $assurance->id }}" @selected(($filtres['assurance_id'] ?? '') == $assurance->id)>
                            {{ $assurance->nom_assureur }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <x-libelle class="small">Statut</x-libelle>
                <select name="statut" class="form-select filtre-input">
                    <option value="">Tous</option>
                    <option value="actif" @selected(($filtres['statut'] ?? '') === 'actif')>Actif</option>
                    <option value="suspendu" @selected(($filtres['statut'] ?? '') === 'suspendu')>Suspendu</option>
                    <option value="termine" @selected(($filtres['statut'] ?? '') === 'termine')>Terminé</option>
                </select>
            </div>
            <div class="col-md-2">
                <x-libelle class="small">Recherche employé</x-libelle>
                <input type="text" name="recherche" class="form-control filtre-input"
                       value="{{ $filtres['recherche'] ?? '' }}" placeholder="Nom...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100">
                    <i class="fa fa-filter"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="main-card">
        <x-tableau class="table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employé</th>
                        <th>Assureur</th>
                        <th>N° de police</th>
                        <th>Date souscription</th>
                        <th class="text-end">Prélèvement mensuel</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assures as $assure)
                        <tr>
                            <td>
                                <strong>{{ $assure->employe?->nom_prenom ?? '—' }}</strong>
                            </td>
                            <td>{{ $assure->assurance->nom_assureur ?? '—' }}</td>
                            <td>{{ $assure->assurance->police_assurance ?? '—' }}</td>
                            <td>{{ $assure->date_souscription?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-end">
                                {{ number_format((float) $assure->prelevement_mensuel, 0, ',', ' ') }} FCFA
                            </td>
                            <td>
                                @php
                                    $badge = match($assure->statut) {
                                        'actif'    => 'success',
                                        'suspendu' => 'warning',
                                        'termine'  => 'secondary',
                                        default    => 'secondary',
                                    };
                                @endphp
                                <x-badge class="bg-{{ $badge }}">{{ ucfirst($assure->statut ?? 'actif') }}</x-badge>
                            </td>
                            <td class="text-end">
                                <x-actions>
                                        @can('assurances.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-assurance"
                                                    data-url="{{ route('rh.assures.edit', $assure) }}">
                                                <i class="fa fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        @can('assurances.gerer')
                                        <x-actions.supprimer :action="route('rh.assures.destroy', $assure)" icone="fa fa-trash" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fa fa-shield-halved fa-2x text-muted" :paragraphe="false" class="text-center py-4"><p class="mt-2 mb-0">Aucune assurance enregistrée.</p></x-vide>
                    @endforelse
                </tbody>
            </x-tableau>
        <div class="p-3">
            <x-pagination :liste="$assures" />
        </div>
    </div>

    {{-- Modale unique (contenu injecté en AJAX) --}}
    <x-modale id="modalAssure" aria-hidden="true" dialogue="modal-lg modal-dialog-centered" contenu-id="modalAssureContent" />

@endsection

@push('js')
<script>
$(function () {
    const modalEl   = document.getElementById('modalAssure');
    const modal     = new bootstrap.Modal(modalEl);
    const $contenu  = $('#modalAssureContent');

    // Ouvre la modale de création
    $('#btn-nouvelle-assurance').on('click', function () {
        $contenu.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modal.show();
        $.get("{{ route('rh.assures.create') }}", function (html) {
            $contenu.html(html);
        }).fail(function () {
            $contenu.html('<div class="p-4 text-danger">Erreur de chargement du formulaire.</div>');
        });
    });

    // Ouvre la modale d'édition
    $(document).on('click', '.btn-edit-assurance', function () {
        const url = $(this).data('url');
        $contenu.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modal.show();
        $.get(url, function (html) {
            $contenu.html(html);
        }).fail(function () {
            $contenu.html('<div class="p-4 text-danger">Erreur de chargement du formulaire.</div>');
        });
    });

    // Soumission AJAX du formulaire (création ou édition)
    $(document).on('submit', '#formAssure', function (e) {
        e.preventDefault();

        const $form = $(this);
        const url   = $form.attr('action');
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        // Nettoyage des erreurs précédentes
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize() + '&_method=' + methode,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(function (reponse) {
            modal.hide();
            if (window.showToastThenReload) {
                window.showToastThenReload(reponse.message || 'Opération réussie.');
            } else {
                window.location.reload();
            }
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $input = $form.find('[name="' + champ + '"]');
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                if (window.showToast) { window.showToast(msg, 'error'); }
                else { alert(msg); }
            }
        });
    });

    // Réinitialise la modale à la fermeture
    modalEl.addEventListener('hidden.bs.modal', function () {
        $contenu.html('');
    });
});
</script>
@endpush