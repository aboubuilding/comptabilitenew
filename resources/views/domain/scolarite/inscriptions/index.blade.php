{{-- resources/views/admin/scolarite/inscriptions/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Inscriptions')
@section('page_icon', 'fa-file-signature')
@section('page_title', 'Inscriptions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Scolarité</li>
    <li class="breadcrumb-item active">Inscriptions</li>
@endsection

@section('page_actions')
    @can('inscriptions.saisir')
    <x-bouton variante="nouveau" type="button" id="btnNouvelleInscription" data-bs-toggle="modal" data-bs-target="#inscriptionModal">
        <i class="fas fa-plus-circle me-1"></i> Nouvelle inscription
    </x-bouton>
    @endcan
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('app/assets/plugins/select2/css/select2.min.css') }}">
<style>
    .inscriptions-page { --insc-bg-soft: var(--eim-cream); --insc-border: var(--eim-border); }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(142,18,22,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--insc-border) !important; }

    .filtre-label { font-size: .74rem; font-weight: 600; color: var(--eim-ink-soft); text-transform: uppercase; letter-spacing: .03em; margin-bottom: 5px; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--insc-border); padding: .5rem .8rem; font-size: .85rem; }
    .search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #7A6A6A; font-size: .8rem; }
    .search-input { padding-left: 32px !important; }

    .btn-filtrer { background: var(--school-red); color: #fff; border: none; border-radius: 10px; padding: .5rem 1rem; font-weight: 600; font-size: .85rem; }

    .compteur-badge {
        display: inline-block; background: var(--insc-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .82rem; padding: .4rem .85rem; border-radius: 999px; border: 1px solid var(--insc-border);
    }

    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(227,30,36,.35); }

    .table-inscriptions thead tr { background: var(--insc-bg-soft); }
    .table-inscriptions thead th {
        color: var(--school-red); font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--insc-border); padding: .8rem .85rem; white-space: nowrap;
    }
    .table-inscriptions tbody td { padding: .7rem .85rem; border-bottom: 1px solid var(--insc-border); font-size: .85rem; color: var(--school-ink); vertical-align: middle; }
    .table-inscriptions tbody tr:hover { background: var(--eim-hover); }
    .table-inscriptions tbody tr:last-child td { border-bottom: none; }

    .eleve-nom { font-weight: 700; font-size: .87rem; display: block; }
    .eleve-matricule { font-family: monospace; font-size: .78rem; color: var(--school-muted); }
    .classe-cell { font-size: .84rem; }
    .classe-cell small { color: var(--school-muted); display: block; font-size: .74rem; }

    .badge-statut { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; padding: .35em .75em; border-radius: 999px; white-space: nowrap; }
    .badge-statut-attente { background: #fdf3e2; color: #b8720b; }
    .badge-statut-validee { background: #e3f7ec; color: var(--eim-success); }
    .badge-statut-rejetee { background: #fbeaea; color: #b13b3b; }
    .badge-statut-abandon { background: var(--insc-bg-soft); color: var(--school-muted); }


    .modal-inscriptions { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-inscriptions { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
    .modal-header-inscriptions .modal-title { font-weight: 600; font-size: 1.05rem; }
    .form-section { background: var(--insc-bg-soft); border: 1px solid var(--insc-border); border-radius: 12px; padding: 1rem 1.1rem; }
    .form-section-title { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--school-red); margin-bottom: .6rem; }
    .form-section .form-control, .form-section .form-select { border-radius: 8px; border: 1px solid var(--insc-border); font-size: .88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(227,30,36,.1); }
    .btn-annuler { background: #fff; border: 1px solid var(--insc-border); color: var(--eim-ink-soft); border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--insc-bg-soft); }

    /* .btn-action / .dropdown-menu-actions viennent de layouts/app.blade.php */
</style>
@endsection

@section('contenu')
    <div class="inscriptions-page">
        <x-carte>
            <div class="card-header bg-white py-3 px-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Cycle</label>
                        <select name="cycle_id" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach ($cyclesOptions as $cycle)
                                <option value="{{ $cycle->id }}" {{ request('cycle_id') == $cycle->id ? 'selected' : '' }}>{{ $cycle->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Niveau</label>
                        <select name="niveau_id" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach ($niveauxOptions as $niveau)
                                <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>{{ $niveau->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Classe</label>
                        <select name="classe_id" class="form-select filtre-input">
                            <option value="">Toutes</option>
                            @foreach ($classesOptions as $classe)
                                <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>{{ $classe->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Statut</label>
                        <select name="statut" class="form-select filtre-input">
                            <option value="">Tous</option>
                            <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                            <option value="validee" {{ request('statut') === 'validee' ? 'selected' : '' }}>Validée</option>
                            <option value="rejetee" {{ request('statut') === 'rejetee' ? 'selected' : '' }}>Rejetée</option>
                            <option value="abandonnee" {{ request('statut') === 'abandonnee' ? 'selected' : '' }}>Abandonnée</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filtre-label">Recherche</label>
                        <x-recherche name="search" placeholder="Nom, matricule..." value="{{ request('search') }}" />
                    </div>
                    <div class="col-6 col-md-1">
                        <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter"></i></x-bouton>
                    </div>
                </form>
                <div class="text-end mt-2">
                    <x-compteur>{{ $inscriptions->total() }} inscription(s)</x-compteur>
                </div>
            </div>

            <div class="card-body p-0">
                <x-tableau class="table-inscriptions align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th>Classe / Niveau / Cycle</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th class="text-end">Frais scolarité</th>
                                <th class="text-end">Remise</th>
                                <th class="text-center" style="width:70px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inscriptions as $inscription)
                                @php $statut = $inscription->statutAffiche(); @endphp
                                <tr>
                                    <td>
                                        <span class="eleve-nom">{{ $inscription->eleve?->nom }} {{ $inscription->eleve?->prenom }}</span>
                                        <span class="eleve-matricule">{{ $inscription->eleve?->matricule ?? '—' }}</span>
                                    </td>
                                    <td class="classe-cell">
                                        {{ $inscription->classe?->libelle ?? '—' }}
                                        <small>{{ $inscription->niveau?->libelle }} · {{ $inscription->cycle?->libelle }}</small>
                                    </td>
                                    <td>{{ $inscription->date_inscription?->format('d/m/Y') }}</td>
                                    <td>{{ $inscription->type_inscription?->label() ?? '—' }}</td>
                                    <td><x-badge class="{{ $statut['classe'] }}">{{ $statut['label'] }}</x-badge></td>
                                    <td class="text-end">{{ number_format($inscription->frais_scolarite ?? 0, 0, ',', ' ') }} F</td>
                                    <td class="text-end">{{ $inscription->taux_remise ?? 0 }} %</td>
                                    <td class="text-center">
                                        <x-actions>
                                                @if ($inscription->isEnAttente())
                                                    @can('inscriptions.valider')
                                                    <li>
                                                        <form action="{{ route('scolarite.inscriptions.valider', $inscription) }}" method="POST"
                                                              class="form-confirm-delete"
                                                              data-confirm-title="Valider cette inscription ?"
                                                              data-confirm-text="L'élève sera considéré comme inscrit pour l'année.">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="fas fa-check-circle"></i> Valider
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-motif"
                                                                data-url="{{ route('scolarite.inscriptions.rejeter', $inscription) }}"
                                                                data-title="Motif du rejet"
                                                                data-bs-toggle="modal" data-bs-target="#motifModal">
                                                            <i class="fas fa-times-circle"></i> Rejeter
                                                        </button>
                                                    </li>
                                                    @endcan
                                                @endif
                                                @if ($inscription->isValidee() && !$inscription->isAbandonnee() && (auth()->user()->can('inscriptions.saisir') || auth()->user()->can('inscriptions.valider')))
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-motif"
                                                                data-url="{{ route('scolarite.inscriptions.abandon', $inscription) }}"
                                                                data-title="Motif de l'abandon"
                                                                data-bs-toggle="modal" data-bs-target="#motifModal">
                                                            <i class="fas fa-user-slash"></i> Enregistrer un abandon
                                                        </button>
                                                    </li>
                                                @endif
                                                @if ($inscription->isValidee() && !$inscription->isAbandonnee())
                                                    <li>
                                                        <a href="{{ route('scolarite.inscriptions.certificat', $inscription) }}" class="dropdown-item">
                                                            <i class="fas fa-certificate"></i> Certificat de scolarité
                                                        </a>
                                                    </li>
                                                @endif
                                            </x-actions>
                                    </td>
                                </tr>
                            @empty
                                <x-vide colspan="8" icone="fas fa-file-signature" :paragraphe="false"><p class="mb-0">Aucune inscription ne correspond à ces critères.</p></x-vide>
                            @endforelse
                        </tbody>
                    </x-tableau>
            </div>
        </x-carte>
        <div class="mt-3">
            <x-pagination :liste="$inscriptions->appends(request()->query())" />
        </div>

        {{-- Modale de création --}}
        @can('inscriptions.saisir')
        <x-modale id="inscriptionModal" data-bs-backdrop="static" aria-hidden="true" dialogue="modal-lg" contenu-class="modal-inscriptions" entete-class="modal-header-inscriptions" corps-class="p-4" croix-blanche>
            <x-slot:formulaire id="inscription-form">@csrf</x-slot:formulaire>
            <x-slot:titre><i class="fas fa-plus-circle me-2"></i>Nouvelle inscription</x-slot:titre>
            <x-section class="mb-3">
                <div class="form-section-title">Élève &amp; classe</div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <x-libelle requis>Élève</x-libelle>
                        <select name="eleve_id" id="insc-eleve" class="form-select" style="width:100%;">
                            <option value=""></option>
                            @foreach ($elevesOptions as $eleve)
                                <option value="{{ $eleve->id }}">{{ $eleve->nom }} {{ $eleve->prenom }} @if($eleve->matricule) ({{ $eleve->matricule }}) @endif</option>
                            @endforeach
                        </select>
                        <div class="text-danger small mt-1" id="insc-eleve_id-error"></div>
                    </div>
                    <div class="col-md-3">
                        <x-libelle requis>Type</x-libelle>
                        <select name="type_inscription" class="form-select">
                            @foreach (\App\Domain\Scolarite\Types\TypeInscription::options() as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <x-libelle requis>Date</x-libelle>
                        <input type="date" name="date_inscription" class="form-control" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <x-libelle requis>Cycle</x-libelle>
                        <select name="cycle_id" class="form-select">
                            <option value="">-- Choisir --</option>
                            @foreach ($cyclesOptions as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <x-libelle requis>Niveau</x-libelle>
                        <select name="niveau_id" class="form-select">
                            <option value="">-- Choisir --</option>
                            @foreach ($niveauxOptions as $niveau)
                                <option value="{{ $niveau->id }}">{{ $niveau->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <x-libelle>Classe</x-libelle>
                        <select name="classe_id" class="form-select">
                            <option value="">-- Choisir --</option>
                            @foreach ($classesOptions as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-section>

            <x-section>
                <div class="form-section-title">Frais &amp; remise</div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <x-libelle>Frais scolarité</x-libelle>
                        <input type="number" step="0.01" name="frais_scolarite" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <x-libelle>Frais inscription</x-libelle>
                        <input type="number" step="0.01" name="frais_inscription" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <x-libelle>Taux de remise (%)</x-libelle>
                        <input type="number" name="taux_remise" class="form-control" min="0" max="100">
                    </div>
                    <div class="col-md-4">
                        <x-libelle>Frais cantine</x-libelle>
                        <input type="number" step="0.01" name="frais_cantine" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <x-libelle>Frais bus</x-libelle>
                        <input type="number" step="0.01" name="frais_bus" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <x-libelle>Frais livre</x-libelle>
                        <input type="number" step="0.01" name="frais_livre" class="form-control">
                    </div>
                </div>
            </x-section>
            <x-slot:pied>
                <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
                <x-bouton type="submit"><i class="fas fa-save me-1"></i>Enregistrer</x-bouton>
            </x-slot:pied>
        </x-modale>

        @endcan

        @canany(['inscriptions.saisir', 'inscriptions.valider'])
        {{-- Modale générique motif (rejet ou abandon — même formulaire,
             action/titre définis en JS selon le bouton cliqué). Soumission
             en POST classique (pas d'AJAX) : rechargement + flash de
             session standard. --}}
        <x-modale id="motifModal" data-bs-backdrop="static" aria-hidden="true" dialogue="" contenu-class="modal-inscriptions" entete-class="modal-header-inscriptions" corps-class="p-4" croix-blanche>
            <x-slot:formulaire id="motif-form" method="POST" action="">@csrf</x-slot:formulaire>
            <x-slot:titre id="motif-modal-title">Motif</x-slot:titre>
            <x-libelle requis>Motif</x-libelle>
            <textarea name="motif" id="motif-textarea" class="form-control" rows="3" required></textarea>
            <x-slot:pied>
                <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
                <x-bouton type="submit">Confirmer</x-bouton>
            </x-slot:pied>
        </x-modale>
        @endcanany
    </div>
@endsection

@push('js')
<script src="{{ asset('app/assets/plugins/select2/js/select2.min.js') }}"></script>
<script>
$(function () {
    $('#insc-eleve').select2({
        dropdownParent: $('#inscriptionModal'),
        placeholder: 'Rechercher un élève...',
        width: '100%',
    });

    $(document).on('click', '.btn-motif', function () {
        $('#motif-form').attr('action', $(this).data('url'));
        $('#motif-modal-title').text($(this).data('title'));
        $('#motif-textarea').val('');
    });

    const $form = $('#inscription-form');
    const $eleveError = $('#insc-eleve_id-error');

    $form.on('submit', function (e) {
        e.preventDefault();
        $eleveError.text('');
        $('#insc-eleve').removeClass('is-invalid');

        $.ajax({
            url: '{{ route('scolarite.inscriptions.store') }}',
            method: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('inscriptionModal')).hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    if (errors.eleve_id) { $eleveError.text(errors.eleve_id[0]); }
                    const firstMsg = Object.values(errors)[0][0];
                    window.showToast(firstMsg, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush