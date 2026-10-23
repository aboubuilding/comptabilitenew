@extends('admin.layouts.app')

@section('title', 'Carburant véhicules')
@section('page_icon', 'fa-gas-pump')
@section('page_title', 'Pleins de carburant')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Carburant</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.carburants.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.entretiens.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-tools me-1"></i> Entretiens
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauPlein" data-bs-toggle="modal" data-bs-target="#carburantModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau plein
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    /* Réutilise les classes de entretiens/index — non dupliqué pour concision */
    .cb-page { --c-border:var(--eim-border); --c-bg-soft:var(--eim-cream); }
    .stat-card { background:#fff; border:1px solid var(--c-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--c-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--c-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--c-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--c-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--c-border); }

    .table-cb thead tr { background:var(--c-bg-soft); }
    .table-cb thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--c-border); white-space:nowrap; }
    .table-cb tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--c-border); font-size:.9rem; vertical-align:middle; }
    .table-cb tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-cb { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-cb { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--c-bg-soft); border:1px solid var(--c-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--c-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--c-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="cb-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-gas-pump" couleur="blue" libelle="Pleins">{{ $stats['nb'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-tint" couleur="gold" libelle="Litres consommés">{{ number_format($stats['litres'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="red" libelle="Montant total">{{ number_format($stats['montant'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-tag" couleur="green" libelle="Prix moyen / litre">{{ number_format($stats['prix_moyen'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    {{-- Consommation par véhicule --}}
    @if ($parVehicule->isNotEmpty())
        <x-carte class="mb-4">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-1" style="color:var(--school-gold-dark);"></i> Consommation par véhicule</h6>
            </div>
            <div class="card-body p-0">
                <x-tableau class="table-cb mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Véhicule</th>
                                <th class="text-end">Pleins</th>
                                <th class="text-end">Litres</th>
                                <th class="text-end pe-4">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($parVehicule as $p)
                            <tr>
                                <td class="ps-4 fw-semibold">
                                    {{ $p->voiture?->plaque }} <span class="text-muted small">{{ $p->voiture?->marque }}</span>
                                </td>
                                <td class="num">{{ $p->nb }}</td>
                                <td class="num">{{ number_format((float) $p->litres, 0, ',', ' ') }} L</td>
                                <td class="num grand pe-4">{{ number_format((float) $p->montant, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </x-tableau>
            </div>
        </x-carte>
    @endif

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Station, facture..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="voiture_id" class="form-select filtre-input">
                        <option value="">— Véhicule —</option>
                        @foreach ($voitures as $v)
                            <option value="{{ $v->id }}" @selected(($filters['voiture_id'] ?? '') == $v->id)>{{ $v->plaque }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-cb align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Véhicule</th>
                            <th class="text-end">Litres</th>
                            <th class="text-end">P.U.</th>
                            <th class="text-end">Montant</th>
                            <th class="text-end">KM</th>
                            <th>Station</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($carburants as $c)
                        <tr>
                            <td class="ps-4 text-sm">{{ optional($c->date_plein)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                @if ($c->voiture)
                                    <div class="fw-semibold">{{ $c->voiture->plaque }}</div>
                                    <div class="text-xs text-muted">{{ $c->voiture->marque }}</div>
                                @endif
                            </td>
                            <td class="num">{{ number_format((float) $c->quantite_litres, 2, ',', ' ') }} L</td>
                            <td class="num">{{ number_format((float) $c->prix_unitaire, 2, ',', ' ') }}</td>
                            <td class="num grand">{{ number_format((float) $c->montant_total, 2, ',', ' ') }}</td>
                            <td class="num text-sm">{{ number_format((float) $c->kilometrage, 0, ',', ' ') }}</td>
                            <td class="text-sm text-muted">{{ $c->station_service ?? '—' }}</td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-cb"
                                                    data-id="{{ $c->id }}"
                                                    data-voiture="{{ $c->voiture_id }}"
                                                    data-date="{{ optional($c->date_plein)->toDateString() }}"
                                                    data-litres="{{ $c->quantite_litres }}"
                                                    data-prix="{{ $c->prix_unitaire }}"
                                                    data-km="{{ $c->kilometrage }}"
                                                    data-station="{{ $c->station_service }}"
                                                    data-facture="{{ $c->facture }}"
                                                    data-bs-toggle="modal" data-bs-target="#carburantModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <x-actions.supprimer :action="route('services-eleves.transport.carburants.destroy', $c)">Supprimer</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="8" icone="fas fa-gas-pump">Aucun plein enregistré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$carburants->appends(request()->query())" /></div>

    @can('transport.gerer')
    <x-modale id="carburantModal" data-bs-backdrop="static" contenu-class="modal-cb" entete-class="modal-header-cb" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="carburant-form">@csrf
                    <input type="hidden" name="_method" id="cb-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="cb-title"><i class="fas fa-gas-pump me-2"></i>Nouveau plein</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Véhicule</x-libelle>
                <select name="voiture_id" id="cb-voiture" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($voitures as $v)
                        <option value="{{ $v->id }}">{{ $v->plaque }} ({{ $v->marque }})</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Date</x-libelle>
                    <input type="date" name="date_plein" id="cb-date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Kilométrage</x-libelle>
                    <input type="number" name="kilometrage" id="cb-km" min="0" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Litres</x-libelle>
                    <input type="number" name="quantite_litres" id="cb-litres" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Prix unitaire</x-libelle>
                    <input type="number" name="prix_unitaire" id="cb-prix" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle>Station</x-libelle>
                    <input type="text" name="station_service" id="cb-station" class="form-control" maxlength="150">
                </div>
                <div class="col-md-6">
                    <x-libelle>N° facture</x-libelle>
                    <input type="text" name="facture" id="cb-facture" class="form-control" maxlength="100">
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
    const $form = $('#carburant-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouveauPlein').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#cb-method').val('POST');
        $('#cb-title').html('<i class="fas fa-gas-pump me-2"></i>Nouveau plein');
    });

    $(document).on('click', '.btn-edit-cb', function () {
        currentId = $(this).data('id');
        $('#cb-voiture').val($(this).data('voiture'));
        $('#cb-date').val($(this).data('date'));
        $('#cb-litres').val($(this).data('litres'));
        $('#cb-prix').val($(this).data('prix'));
        $('#cb-km').val($(this).data('km'));
        $('#cb-station').val($(this).data('station'));
        $('#cb-facture').val($(this).data('facture'));
        $('#cb-method').val('PUT');
        $('#cb-title').html('<i class="fas fa-pen me-2"></i>Modifier le plein');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/carburants') }}/${currentId}`
            : `{{ route('services-eleves.transport.carburants.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#carburantModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            }
        });
    });
});
</script>
@endpush