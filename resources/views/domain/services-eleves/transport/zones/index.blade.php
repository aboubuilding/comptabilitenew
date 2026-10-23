@extends('admin.layouts.app')

@section('title', 'Zones')
@section('page_icon', 'fa-map-marked-alt')
@section('page_title', 'Zones de transport')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Zones</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.voitures.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-bus me-1"></i> Véhicules
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.lignes.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-route me-1"></i> Lignes
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelleZone" data-bs-toggle="modal" data-bs-target="#zoneModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle zone
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .zone-page { --z-bg-soft:var(--eim-cream); --z-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--z-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--z-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--z-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--z-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--z-border); }

    .table-zones thead tr { background:var(--z-bg-soft); }
    .table-zones thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--z-border); }
    .table-zones tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--z-border); font-size:.9rem; vertical-align:middle; }
    .table-zones tbody tr:hover { background:var(--eim-hover); }

    .zone-libelle { font-weight:700; color:var(--school-ink); }
    .zone-desc { font-size:.75rem; color:var(--school-muted); }

    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    .chip { display:inline-flex; align-items:center; gap:4px; background:var(--z-bg-soft); color:var(--school-red); font-size:.72rem; font-weight:600; padding:.25em .7em; border-radius:999px; border:1px solid var(--z-border); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-zone { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-zone { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--z-bg-soft); border:1px solid var(--z-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--z-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--z-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="zone-page">
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <x-recherche name="search" placeholder="Rechercher une zone..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="ligne_id" class="form-select filtre-input" id="filtre-ligne">
                        <option value="">— Toutes les lignes —</option>
                        @foreach ($lignes as $l)
                            <option value="{{ $l->id }}" @selected(($filters['ligne_id'] ?? '') == $l->id)>{{ $l->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
                <div class="col-md-3 text-end">
                    <x-compteur>{{ $zones->total() }} zone(s)</x-compteur>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-zones align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Zone</th>
                            <th>Ligne (chauffeur, véhicule)</th>
                            <th class="text-end">Tarif indicatif / mois</th>
                            <th class="text-center">Abonnés</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($zones as $z)
                        <tr>
                            <td class="ps-4">
                                <div class="zone-libelle">{{ $z->libelle }}</div>
                                @if ($z->description)
                                    <div class="zone-desc">{{ \Str::limit($z->description, 80) }}</div>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($z->ligne)
                                    <div class="fw-semibold"><i class="fas fa-route me-1 text-muted"></i>{{ $z->ligne->libelle }}</div>
                                    <div class="zone-desc">
                                        @if ($z->ligne->chauffeur)<i class="fas fa-user-tie me-1"></i>{{ $z->ligne->chauffeur->nom }} {{ $z->ligne->chauffeur->prenom }}@endif
                                        @if ($z->ligne->voiture)<span class="chip ms-1"><i class="fas fa-bus"></i>{{ $z->ligne->voiture->plaque }}</span>@endif
                                    </div>
                                @else
                                    <span class="text-muted">Aucune ligne</span>
                                @endif
                            </td>
                            <td class="num">{{ (float) $z->tarif_base > 0 ? number_format((float) $z->tarif_base, 0, ',', ' ') : '—' }}</td>
                            <td class="text-center"><x-compteur>{{ $z->abonnements_count ?? 0 }}</x-compteur></td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-zone"
                                                    data-id="{{ $z->id }}"
                                                    data-libelle="{{ $z->libelle }}"
                                                    data-description="{{ $z->description }}"
                                                    data-ligne="{{ $z->ligne_id }}"
                                                    data-tarif="{{ (float) $z->tarif_base ?: '' }}"
                                                    data-bs-toggle="modal" data-bs-target="#zoneModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <x-actions.supprimer :action="route('services-eleves.transport.zones.destroy', $z)" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-map-marked-alt">Aucune zone enregistrée.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$zones->appends(request()->query())" /></div>

    @can('transport.gerer')
    <x-modale id="zoneModal" data-bs-backdrop="static" contenu-class="modal-zone" entete-class="modal-header-zone" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="zone-form">@csrf
                    <input type="hidden" name="_method" id="zone-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="zone-title"><i class="fas fa-map-marked-alt me-2"></i>Nouvelle zone</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Libellé</x-libelle>
                <input type="text" name="libelle" id="z-libelle" class="form-control" required maxlength="150">
                <div class="text-danger text-xs mt-1" id="libelle-error"></div>
            </div>
            <div class="mb-3">
                <x-libelle>Description</x-libelle>
                <textarea name="description" id="z-description" rows="2" class="form-control" maxlength="1000"></textarea>
            </div>
            <div class="row g-3">
                <div class="col-md-7">
                    <x-libelle>Ligne</x-libelle>
                    <select name="ligne_id" id="z-ligne" class="form-select">
                        <option value="">— Aucune —</option>
                        @foreach ($lignes as $l)
                            <option value="{{ $l->id }}">{{ $l->libelle }}@if ($l->voiture) — {{ $l->voiture->plaque }}@endif</option>
                        @endforeach
                    </select>
                    <div class="form-text small">Le chauffeur et le véhicule sont ceux de la ligne.</div>
                </div>
                <div class="col-md-5">
                    <x-libelle>Tarif mensuel indicatif</x-libelle>
                    <input type="number" name="tarif_base" id="z-tarif" min="0" step="1" class="form-control">
                    <div class="form-text small">Proposé à la souscription, modifiable.</div>
                    <div class="text-danger text-xs mt-1" id="tarif_base-error"></div>
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
    const $form = $('#zone-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelleZone').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#zone-method').val('POST');
        $('#zone-title').html('<i class="fas fa-map-marked-alt me-2"></i>Nouvelle zone');
    });

    $(document).on('click', '.btn-edit-zone', function () {
        currentId = $(this).data('id');
        $('#z-libelle').val($(this).data('libelle'));
        $('#z-description').val($(this).data('description'));
        $('#z-ligne').val($(this).data('ligne') || '');
        $('#z-tarif').val($(this).data('tarif') || '');
        $('#zone-method').val('PUT');
        $('#zone-title').html('<i class="fas fa-pen me-2"></i>Modifier la zone');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/zones') }}/${currentId}`
            : `{{ route('services-eleves.transport.zones.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#zoneModal').modal('hide');
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