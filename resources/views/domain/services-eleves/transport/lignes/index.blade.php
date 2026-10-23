@extends('admin.layouts.app')

@section('title', 'Lignes de transport')
@section('page_icon', 'fa-route')
@section('page_title', 'Lignes de transport')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Lignes</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.zones.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-map-marked-alt me-1"></i> Zones
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelleLigne" data-bs-toggle="modal" data-bs-target="#ligneModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle ligne
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .ligne-page { --l-bg-soft:var(--eim-cream); --l-border:var(--eim-border); }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--l-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--l-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--l-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--l-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--l-border); }

    .table-lignes thead tr { background:var(--l-bg-soft); }
    .table-lignes thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--l-border); }
    .table-lignes tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--l-border); font-size:.9rem; vertical-align:middle; }
    .table-lignes tbody tr:hover { background:var(--eim-hover); }

    .ligne-periode { font-size:.75rem; color:var(--school-muted); }
    .chip { display:inline-flex; align-items:center; gap:4px; background:var(--l-bg-soft); color:var(--school-red); font-size:.72rem; font-weight:600; padding:.25em .7em; border-radius:999px; border:1px solid var(--l-border); }
    .zones-ligne { font-size:.75rem; color:var(--school-muted); }
    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .modal-l { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-l { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--l-bg-soft); border:1px solid var(--l-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--l-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--l-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="ligne-page">
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control filtre-input"
                           placeholder="Rechercher une ligne..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
                <div class="col-md-3 text-end">
                    <x-compteur>{{ $lignes->total() }} ligne(s)</x-compteur>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-lignes align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Ligne / période</th>
                            <th>Chauffeur</th>
                            <th>Véhicule</th>
                            <th>Zones desservies</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($lignes as $l)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $l->libelle }}</div>
                                <div class="ligne-periode">
                                    <i class="far fa-calendar me-1"></i>
                                    @if ($l->date_debut || $l->date_fin)
                                        {{ $l->date_debut?->format('d/m/Y') ?? '…' }} → {{ $l->date_fin?->format('d/m/Y') ?? '…' }}
                                    @else
                                        Période non précisée
                                    @endif
                                </div>
                            </td>
                            <td class="text-sm">
                                @if ($l->chauffeur)
                                    <i class="fas fa-user-tie me-1 text-muted"></i>{{ $l->chauffeur->nom }} {{ $l->chauffeur->prenom }}
                                    @if ($l->chauffeur->telephone)<div class="zones-ligne">{{ $l->chauffeur->telephone }}</div>@endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($l->voiture)
                                    <span class="chip"><i class="fas fa-bus"></i>{{ $l->voiture->plaque }}</span>
                                    <div class="zones-ligne">{{ $l->voiture->marque }} {{ $l->voiture->modele }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <x-compteur>{{ $l->zones->count() }} zone(s) · {{ $l->abonnements_count }} abonné(s)</x-compteur>
                                @if ($l->zones->isNotEmpty())
                                    <div class="zones-ligne mt-1" title="{{ $l->zones->pluck('libelle')->implode(', ') }}">{{ \Str::limit($l->zones->pluck('libelle')->implode(', '), 70) }}</div>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-ligne"
                                                    data-id="{{ $l->id }}"
                                                    data-libelle="{{ $l->libelle }}"
                                                    data-chauffeur="{{ $l->chauffeur_id }}"
                                                    data-voiture="{{ $l->voiture_id }}"
                                                    data-debut="{{ $l->date_debut?->toDateString() }}"
                                                    data-fin="{{ $l->date_fin?->toDateString() }}"
                                                    data-bs-toggle="modal" data-bs-target="#ligneModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><a class="dropdown-item" href="{{ route('services-eleves.transport.zones.index', ['ligne_id' => $l->id]) }}"><i class="fas fa-map-marked-alt"></i> Voir ses zones</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <x-actions.supprimer :action="route('services-eleves.transport.lignes.destroy', $l)" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-route">Aucune ligne enregistrée.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$lignes->appends(request()->query())" /></div>

    @can('transport.gerer')
    <x-modale id="ligneModal" data-bs-backdrop="static" contenu-class="modal-l" entete-class="modal-header-l" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="ligne-form">@csrf
                    <input type="hidden" name="_method" id="ligne-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="ligne-title"><i class="fas fa-route me-2"></i>Nouvelle ligne</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Libellé</x-libelle>
                <input type="text" name="libelle" id="l-libelle" class="form-control" required maxlength="150">
                <div class="text-danger text-xs mt-1" id="libelle-error"></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle>Chauffeur</x-libelle>
                    <select name="chauffeur_id" id="l-chauffeur" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach ($chauffeurs as $c)
                            <option value="{{ $c->id }}">{{ $c->nom }} {{ $c->prenom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Véhicule</x-libelle>
                    <select name="voiture_id" id="l-voiture" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach ($voitures as $v)
                            <option value="{{ $v->id }}">{{ $v->plaque }} ({{ $v->marque }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Début de la période</x-libelle>
                    <input type="date" name="date_debut" id="l-debut" class="form-control">
                </div>
                <div class="col-md-6">
                    <x-libelle>Fin de la période</x-libelle>
                    <input type="date" name="date_fin" id="l-fin" class="form-control">
                    <div class="text-danger text-xs mt-1" id="date_fin-error"></div>
                </div>
            </div>
            <div class="form-text small mt-2">Les zones de la ligne et leurs abonnés suivent le chauffeur et le véhicule choisis.</div>
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
    const $form = $('#ligne-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelleLigne').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#ligne-method').val('POST');
        $('#ligne-title').html('<i class="fas fa-route me-2"></i>Nouvelle ligne');
    });

    $(document).on('click', '.btn-edit-ligne', function () {
        currentId = $(this).data('id');
        $('#l-libelle').val($(this).data('libelle'));
        $('#l-chauffeur').val($(this).data('chauffeur') || '');
        $('#l-voiture').val($(this).data('voiture') || '');
        $('#l-debut').val($(this).data('debut') || '');
        $('#l-fin').val($(this).data('fin') || '');
        $('#ligne-method').val('PUT');
        $('#ligne-title').html('<i class="fas fa-pen me-2"></i>Modifier la ligne');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/lignes') }}/${currentId}`
            : `{{ route('services-eleves.transport.lignes.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#ligneModal').modal('hide');
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
});
</script>
@endpush