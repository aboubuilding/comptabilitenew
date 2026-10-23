@extends('admin.layouts.app')

@section('page_title', 'Élèves')
@section('page_icon', 'fa-user-graduate')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Scolarité</a></li>
    <li class="breadcrumb-item active">Élèves</li>
@endsection

@section('page_actions')
    @can('eleves.gerer')
    <button type="button" class="btn-nouveau" id="btn-nouvel-eleve">
        <i class="fa fa-plus"></i> Nouvel élève
    </button>
    @endcan
    <a href="{{ route('scolarite.eleves.export', request()->query()) }}" class="btn-outline-scolarite ms-2">
        <i class="fa fa-file-excel"></i> Exporter
    </a>
@endsection

@section('css')
<style>
    .stat-card { border-left: 4px solid var(--school-red); }
    .eleve-photo { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
    .classe-cell small { display:block; color: var(--school-muted); font-size: 11px; }
    .bulk-bar {
        position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
        background: var(--school-ink); color: white; padding: 12px 24px;
        border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.25);
        z-index: 1050; display: none;
    }
    .bulk-bar.visible { display: flex; align-items: center; gap: 16px; }
</style>
@endsection

@section('contenu')

    {{-- Bandeau année --}}
    <div class="alert alert-info d-flex align-items-center mb-3">
        <i class="fa fa-calendar-alt me-2"></i>
        <span>
            Liste des élèves <strong>inscrits pour l'année scolaire active</strong>.
            @unless($anneeId)
                <span class="badge bg-warning text-dark ms-2">Aucune année active</span>
            @endunless
            <small class="text-muted ms-2">— Changez d'année via le sélecteur en en-tête.</small>
        </span>
    </div>

    {{-- Stats --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="stat-card p-3 bg-white rounded shadow-sm">
                <div class="text-muted small"><i class="fa fa-users me-1"></i> Élèves inscrits (année active)</div>
                <div class="h4 mb-0">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card p-3 bg-white rounded shadow-sm">
                <div class="text-muted small"><i class="fa fa-mars me-1"></i> Masculins</div>
                <div class="h4 mb-0">{{ $stats['masculins'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card p-3 bg-white rounded shadow-sm">
                <div class="text-muted small"><i class="fa fa-venus me-1"></i> Féminins</div>
                <div class="h4 mb-0">{{ $stats['feminins'] }}</div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="main-card p-3 mb-3">
        <form method="GET" action="{{ route('scolarite.eleves.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <x-libelle class="small">Recherche</x-libelle>
                <input type="text" name="recherche" class="form-control filtre-input"
                       value="{{ $filtres['recherche'] ?? '' }}" placeholder="Nom, prénom ou matricule...">
            </div>
            <div class="col-md-2">
                <x-libelle class="small">Sexe</x-libelle>
                <select name="sexe" class="form-select filtre-input">
                    <option value="">Tous</option>
                    <option value="1" @selected(($filtres['sexe'] ?? '') == 1)>Masculin</option>
                    <option value="2" @selected(($filtres['sexe'] ?? '') == 2)>Féminin</option>
                </select>
            </div>
            <div class="col-md-2">
                <x-libelle class="small">Nationalité</x-libelle>
                <select name="nationalite_id" class="form-select filtre-input">
                    <option value="">Toutes</option>
                    @foreach($nationalites as $n)
                        <option value="{{ $n->id }}" @selected(($filtres['nationalite_id'] ?? '') == $n->id)>{{ $n->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <x-libelle class="small">Classe</x-libelle>
                <select name="classe_id" class="form-select filtre-input">
                    <option value="">Toutes</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected(($filtres['classe_id'] ?? '') == $c->id)>{{ $c->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <x-libelle class="small">Situation</x-libelle>
                <select name="situation" class="form-select filtre-input">
                    <option value="inscrits" @selected(($filtres['situation'] ?? 'inscrits') === 'inscrits')>Inscrits de l'année</option>
                    <option value="sans" @selected(($filtres['situation'] ?? '') === 'sans')>Sans inscription validée</option>
                    <option value="tous" @selected(($filtres['situation'] ?? '') === 'tous')>Tous les élèves</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn-filtrer w-100"><i class="fa fa-filter"></i></button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('scolarite.eleves.index') }}" class="btn-annuler w-100" title="Réinitialiser">Réinitialiser</a>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="main-card">
        <x-tableau class="table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;">
                            <input type="checkbox" id="check-all" class="form-check-input">
                        </th>
                        <th>Photo</th>
                        <th>Matricule</th>
                        <th>Nom & prénom</th>
                        <th>Sexe</th>
                        <th>Naissance</th>
                        <th>Nationalité</th>
                        <th>Classe / Niveau · Cycle</th>
                        <th class="text-end">Montant dû</th>
                        <th class="text-end">Encaissé</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eleves as $e)
                        @php $insc = $e->inscriptionActive; @endphp
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input check-eleve"
                                       value="{{ $e->id }}"
                                       data-niveau-id="{{ $insc->niveau_id ?? '' }}">
                            </td>
                            <td>
                                @if($e->photo)
                                    <img src="{{ Storage::url($e->photo) }}" class="eleve-photo" alt="">
                                @else
                                    <div class="eleve-photo bg-secondary text-white d-flex align-items-center justify-content-center">
                                        {{ strtoupper(substr($e->nom ?? '?', 0, 1)) }}
                                    </div>
                                @endif
                            </td>
                            <td><code>{{ $e->matricule }}</code></td>
                            <td><strong>{{ $e->nom }}</strong> {{ $e->prenom }}</td>
                            <td>{{ $e->sexe?->initiale() ?? '—' }}</td>
                            <td>{{ $e->date_naissance?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $e->nationalite->libelle ?? '—' }}</td>
                            <td class="classe-cell">
                                {{-- Classe : badge OU "Non affecté" --}}
                                @if($insc?->classe)
                                    <x-badge class="bg-primary">{{ $insc->classe->libelle }}</x-badge>
                                @else
                                    <x-badge class="bg-warning text-dark">Non affecté</x-badge>
                                @endif

                                {{-- Niveau + Cycle : TOUJOURS affichés --}}
                                <small>
                                    @if($insc?->niveau)
                                        {{ $insc->niveau->libelle }}
                                        @if($insc?->cycle)
                                            · {{ $insc->cycle->libelle }}
                                        @endif
                                    @else
                                        —
                                    @endif
                                </small>
                            </td>
                            <td class="text-end {{ ($e->montant_du ?? 0) > 0 ? 'text-danger fw-bold' : '' }}">
                                {{ number_format($e->montant_du ?? 0, 0, ',', ' ') }}
                            </td>
                            <td class="text-end text-success">
                                {{ number_format($e->montant_encaisse ?? 0, 0, ',', ' ') }}
                            </td>
                            <td>
                                <x-badge class="bg-{{ $e->etat ? 'success' : 'secondary' }}">
                                    {{ $e->etat ? 'Actif' : 'Inactif' }}
                                </x-badge>
                            </td>
                            <td class="text-end">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('scolarite.eleves.show', $e) }}">
                                                <i class="fa fa-eye"></i> Consulter
                                            </a>
                                        </li>
                                        @can('eleves.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-eleve"
                                                    data-url="{{ route('scolarite.eleves.edit', $e) }}">
                                                <i class="fa fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item btn-affecter-classe"
                                                    data-eleve-id="{{ $e->id }}"
                                                    data-nom="{{ $e->nom_complet }}">
                                                <i class="fa fa-chalkboard"></i> Affecter une classe
                                            </button>
                                        </li>
                                        @endcan
                                        <li>
                                            <a class="dropdown-item" href="{{ route('scolarite.eleves.fiche-pdf', $e) }}">
                                                <i class="fa fa-file-pdf"></i> Fiche PDF
                                            </a>
                                        </li>
                                        @can('eleves.gerer')
                                        <x-actions.supprimer :action="route('scolarite.eleves.destroy', $e)" icone="fa fa-archive">Archiver</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="12" icone="fa fa-user-graduate fa-2x text-muted" :paragraphe="false" class="text-center py-4"><p class="mt-2 mb-0">Aucun élève inscrit pour l'année active.</p></x-vide>
                    @endforelse
                </tbody>
            </x-tableau>
        <div class="p-3">
            <x-pagination :liste="$eleves" />
        </div>
    </div>

    {{-- Barre flottante d'action groupée --}}
    <div class="bulk-bar" id="bulk-bar">
        <span><strong id="bulk-count">0</strong> élève(s) sélectionné(s)</span>
        <button type="button" class="btn btn-warning btn-sm" id="btn-affecter-groupe">
            <i class="fa fa-chalkboard"></i> Affecter une classe
        </button>
        <button type="button" class="btn btn-outline-light btn-sm" id="btn-clear-selection">
            <i class="fa fa-times"></i> Annuler
        </button>
    </div>

    {{-- Modale élève --}}
    <x-modale id="modalEleve" aria-hidden="true" dialogue="modal-xl modal-dialog-centered" contenu-id="modalEleveContent" />

    {{-- Modale affectation --}}
    <x-modale id="modalAffectation" aria-hidden="true" dialogue="modal-lg modal-dialog-centered" contenu-id="modalAffectationContent" />

@endsection

@push('js')
<script>
$(function () {
    // ============================================================
    // Modale élève
    // ============================================================
    const modalEleveEl  = document.getElementById('modalEleve');
    const modalEleve    = new bootstrap.Modal(modalEleveEl);
    const $contenuEleve = $('#modalEleveContent');

    function chargerEleve(url) {
        $contenuEleve.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modalEleve.show();
        $.get(url, html => $contenuEleve.html(html))
         .fail(() => $contenuEleve.html('<div class="p-4 text-danger">Erreur de chargement.</div>'));
    }

    $('#btn-nouvel-eleve').on('click', () => chargerEleve("{{ route('scolarite.eleves.create') }}"));
    $(document).on('click', '.btn-edit-eleve', function () { chargerEleve($(this).data('url')); });

    $(document).on('submit', '#formEleve', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url   = $form.attr('action');
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        const formData = new FormData($form[0]);
        if (methode !== 'POST') formData.append('_method', methode);

        $.ajax({
            url, type: 'POST',
            data: formData, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(r => {
            modalEleve.hide();
            if (window.showToastThenReload) window.showToastThenReload(r.message || 'OK');
            else window.location.reload();
        })
        .fail(xhr => {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $input = $form.find('[name="' + champ + '"]');
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                if (window.showToast) window.showToast(msg, 'error'); else alert(msg);
            }
        });
    });

    modalEleveEl.addEventListener('hidden.bs.modal', () => $contenuEleve.html(''));

    // ============================================================
    // Sélection groupée
    // ============================================================
    const $checkAll  = $('#check-all');
    const $bulkBar   = $('#bulk-bar');
    const $bulkCount = $('#bulk-count');

    function mettreAJourBarre() {
        const n = $('.check-eleve:checked').length;
        $bulkCount.text(n);
        if (n > 0) $bulkBar.addClass('visible');
        else       $bulkBar.removeClass('visible');
    }

    $checkAll.on('change', function () {
        $('.check-eleve').prop('checked', this.checked);
        mettreAJourBarre();
    });

    $(document).on('change', '.check-eleve', function () {
        const total   = $('.check-eleve').length;
        const checked = $('.check-eleve:checked').length;
        $checkAll.prop('checked', total > 0 && checked === total);
        mettreAJourBarre();
    });

    $('#btn-clear-selection').on('click', function () {
        $('.check-eleve, #check-all').prop('checked', false);
        mettreAJourBarre();
    });

    // ============================================================
    // Modale affectation
    // ============================================================
    const modalAffEl  = document.getElementById('modalAffectation');
    const modalAff    = new bootstrap.Modal(modalAffEl);
    const $contenuAff = $('#modalAffectationContent');

    function ouvrirAffectation(eleveIds) {
        $contenuAff.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modalAff.show();
        $.ajax({
            url: "{{ route('scolarite.eleves.affecter-form') }}",
            type: 'GET',
            data: { eleve_ids: eleveIds },
            traditional: true,
        })
        .done(html => $contenuAff.html(html))
        .fail(xhr => {
            const msg = xhr.responseJSON?.message || 'Erreur de chargement.';
            $contenuAff.html('<div class="p-4 text-danger">' + msg + '</div>');
        });
    }

    $(document).on('click', '.btn-affecter-classe', function () {
        ouvrirAffectation([$(this).data('eleve-id')]);
    });

    $('#btn-affecter-groupe').on('click', function () {
        const ids = $('.check-eleve:checked').map(function () { return $(this).val(); }).get();
        if (ids.length === 0) return;
        ouvrirAffectation(ids);
    });

    $(document).on('submit', '#formAffectation', function (e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(r => {
            modalAff.hide();
            if (window.showToastThenReload) window.showToastThenReload(r.message || 'OK');
            else window.location.reload();
        })
        .fail(xhr => {
            const msg = xhr.responseJSON?.message || 'Erreur.';
            if (window.showToast) window.showToast(msg, 'error'); else alert(msg);
        });
    });

    modalAffEl.addEventListener('hidden.bs.modal', () => $contenuAff.html(''));
});
</script>
@endpush