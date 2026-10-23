@extends('admin.layouts.app')

@section('title', 'Véhicules')
@section('page_icon', 'fa-bus')
@section('page_title', 'Parc de véhicules')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item active">Transport scolaire</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.voitures.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.chauffeurs.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-user-tie me-1"></i> Chauffeurs
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.zones.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-map-marked-alt me-1"></i> Zones
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.abonnements.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-id-card me-1"></i> Abonnements
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelleVoiture" data-bs-toggle="modal" data-bs-target="#voitureModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau véhicule
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .transport-page { --tr-bg-soft:var(--eim-cream); --tr-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--tr-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--tr-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--tr-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--tr-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--tr-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--tr-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--tr-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-vehicules thead tr { background:var(--tr-bg-soft); }
    .table-vehicules thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--tr-border); white-space:nowrap; }
    .table-vehicules tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--tr-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-vehicules tbody tr:hover { background:var(--eim-hover); }

    .plaque { font-family:monospace; font-weight:700; color:var(--school-red-dark); font-size:.95rem; }
    .vehicule-modele { font-size:.8rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }

    .badge-statut.disponible  { background:#e3f7ec; color:var(--eim-success); }
    .badge-statut.maintenance { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.sorti       { background:#e0ecfc; color:#2563eb; }
    .badge-statut.reforme     { background:#eceff2; color:var(--eim-ink-soft); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-transport { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-transport { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--tr-bg-soft); border:1px solid var(--tr-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--tr-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--tr-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="transport-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-bus" couleur="blue" libelle="Véhicules total">{{ $compteurs['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-check-circle" couleur="green" libelle="Disponibles">{{ $compteurs['disponible'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-tools" couleur="gold" libelle="En maintenance">{{ $compteurs['maintenance'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-archive" couleur="red" libelle="Réformés">{{ $compteurs['reforme'] }}</x-stat>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <x-recherche name="search" placeholder="Plaque, marque, modèle..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('services-eleves.transport.voitures.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-vehicules align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Plaque / Modèle</th>
                            <th>Chauffeur affecté</th>
                            <th>Zones desservies</th>
                            <th class="text-end">Kilométrage</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($voitures as $v)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('services-eleves.transport.voitures.show', $v) }}" class="text-decoration-none">
                                    <div class="plaque">{{ $v->plaque }}</div>
                                    <div class="vehicule-modele">{{ $v->marque }} {{ $v->modele }}</div>
                                </a>
                            </td>
                            <td class="text-sm">
                                @php $chauffeur = $v->chauffeurActuel(); @endphp
                                @if ($chauffeur)
                                    <i class="fas fa-user-tie me-1 text-muted"></i>{{ $chauffeur->nom_complet }}
                                    <div class="text-xs text-muted">{{ $chauffeur->telephone }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($v->zones->isNotEmpty())
                                    @foreach ($v->zones->take(2) as $zone)
                                        <x-compteur style="font-size:.7rem;">{{ $zone->libelle }}</x-compteur>
                                    @endforeach
                                    @if ($v->zones->count() > 2)
                                        <span class="text-xs text-muted">+{{ $v->zones->count() - 2 }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="num">{{ number_format((float) $v->kilometrage_actuel, 0, ',', ' ') }} km</td>
                            <td>
                                <x-badge class="{{ $v->statut?->css() }}" point>{{ $v->statut?->label() }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li><a class="dropdown-item" href="{{ route('services-eleves.transport.voitures.show', $v) }}"><i class="fas fa-eye"></i> Détail</a></li>
                                        @can('transport.gerer')
                                            <li>
                                                <button class="dropdown-item btn-edit-voiture"
                                                        data-id="{{ $v->id }}"
                                                        data-marque="{{ $v->marque }}"
                                                        data-modele="{{ $v->modele }}"
                                                        data-plaque="{{ $v->plaque }}"
                                                        data-places="{{ $v->nombre_place }}"
                                                        data-annee-fab="{{ $v->annee_fabrication }}"
                                                        data-chassis="{{ $v->numero_chassis }}"
                                                        data-date-achat="{{ optional($v->date_achat)->toDateString() }}"
                                                        data-prix="{{ $v->prix_achat }}"
                                                        data-fournisseur="{{ $v->fournisseur }}"
                                                        data-km="{{ $v->kilometrage_actuel }}"
                                                        data-statut="{{ $v->statut?->value }}"
                                                        data-bs-toggle="modal" data-bs-target="#voitureModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-actions.supprimer :action="route('services-eleves.transport.voitures.destroy', $v)" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="6" icone="fas fa-bus">Aucun véhicule enregistré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$voitures->appends(request()->query())" /></div>

    @can('transport.gerer')
    <x-modale id="voitureModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-transport" entete-class="modal-header-transport" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="voiture-form">@csrf
                    <input type="hidden" name="_method" id="voiture-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="voiture-title"><i class="fas fa-bus me-2"></i>Nouveau véhicule</x-slot:titre>
        <x-section titre="Identification" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle requis>Plaque</x-libelle>
                    <input type="text" name="plaque" id="v-plaque" class="form-control" maxlength="30" required>
                    <div class="text-danger text-xs mt-1" id="plaque-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Marque</x-libelle>
                    <input type="text" name="marque" id="v-marque" class="form-control" maxlength="100" required>
                </div>
                <div class="col-md-4">
                    <x-libelle>Modèle</x-libelle>
                    <input type="text" name="modele" id="v-modele" class="form-control" maxlength="100">
                </div>
                <div class="col-md-4">
                    <x-libelle>Année de fabrication</x-libelle>
                    <input type="number" name="annee_fabrication" id="v-annee" class="form-control" min="1950" max="{{ date('Y') + 1 }}">
                </div>
                <div class="col-md-4">
                    <x-libelle>Nombre de places</x-libelle>
                    <input type="number" name="nombre_place" id="v-places" class="form-control" min="1" max="200">
                </div>
                <div class="col-md-4">
                    <x-libelle>N° châssis</x-libelle>
                    <input type="text" name="numero_chassis" id="v-chassis" class="form-control" maxlength="100">
                </div>
            </div>
        </x-section>

        <x-section>
            <div class="form-section-title"><i class="fas fa-file-invoice-dollar"></i> Achat & suivi</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle>Date d'achat</x-libelle>
                    <input type="date" name="date_achat" id="v-date" class="form-control">
                </div>
                <div class="col-md-4">
                    <x-libelle>Prix d'achat</x-libelle>
                    <input type="number" name="prix_achat" id="v-prix" step="0.01" min="0" class="form-control">
                </div>
                <div class="col-md-4">
                    <x-libelle>Fournisseur</x-libelle>
                    <input type="text" name="fournisseur" id="v-fournisseur" class="form-control" maxlength="150">
                </div>
                <div class="col-md-6">
                    <x-libelle>Kilométrage actuel</x-libelle>
                    <input type="number" name="kilometrage_actuel" id="v-km" step="1" min="0" value="0" class="form-control">
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Statut</x-libelle>
                    <select name="statut" id="v-statut" class="form-select" required>
                        @foreach ($statuts as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
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
    const $form = $('#voiture-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelleVoiture').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#voiture-method').val('POST');
        $('#voiture-title').html('<i class="fas fa-bus me-2"></i>Nouveau véhicule');
    });

    $(document).on('click', '.btn-edit-voiture', function () {
        currentId = $(this).data('id');
        $('#v-plaque').val($(this).data('plaque'));
        $('#v-marque').val($(this).data('marque'));
        $('#v-modele').val($(this).data('modele'));
        $('#v-annee').val($(this).data('annee-fab'));
        $('#v-places').val($(this).data('places'));
        $('#v-chassis').val($(this).data('chassis'));
        $('#v-date').val($(this).data('date-achat'));
        $('#v-prix').val($(this).data('prix'));
        $('#v-fournisseur').val($(this).data('fournisseur'));
        $('#v-km').val($(this).data('km'));
        $('#v-statut').val($(this).data('statut'));
        $('#voiture-method').val('PUT');
        $('#voiture-title').html('<i class="fas fa-pen me-2"></i>Modifier le véhicule');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/voitures') }}/${currentId}`
            : `{{ route('services-eleves.transport.voitures.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#voitureModal').modal('hide');
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
});
</script>
@endpush