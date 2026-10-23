@extends('admin.layouts.app')

@section('title', 'Entretiens véhicules')
@section('page_icon', 'fa-tools')
@section('page_title', 'Entretiens des véhicules')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Entretiens</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.entretiens.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.carburants.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-gas-pump me-1"></i> Carburant
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.assurances.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-shield-alt me-1"></i> Assurances
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelEntretien" data-bs-toggle="modal" data-bs-target="#entretienModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvel entretien
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .ent-page { --e-bg-soft:var(--eim-cream); --e-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--e-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--e-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--e-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--e-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--e-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--e-border); }

    .table-ent thead tr { background:var(--e-bg-soft); }
    .table-ent thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--e-border); white-space:nowrap; }
    .table-ent tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--e-border); font-size:.9rem; vertical-align:middle; }
    .table-ent tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-type { display:inline-flex; padding:.3em .8em; border-radius:999px; background:var(--e-bg-soft); color:var(--school-red); font-size:.72rem; font-weight:600; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-ent { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-ent { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--e-bg-soft); border:1px solid var(--e-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--e-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--e-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="ent-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-tools" couleur="blue" libelle="Entretiens">{{ $stats['nb'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="red" libelle="Coût total">{{ number_format($stats['cout_total'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-list" couleur="gold" libelle="Types différents">{{ count($parType) }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3 text-end d-flex align-items-center justify-content-end">
            <x-compteur>{{ $entretiens->total() }} entretien(s)</x-compteur>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Type, observations..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="voiture_id" class="form-select filtre-input">
                        <option value="">— Véhicule —</option>
                        @foreach ($voitures as $v)
                            <option value="{{ $v->id }}" @selected(($filters['voiture_id'] ?? '') == $v->id)>{{ $v->plaque }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type_entretien" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected(($filters['type_entretien'] ?? '') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-ent align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Véhicule</th>
                            <th>Type</th>
                            <th class="text-end">Kilométrage</th>
                            <th class="text-end">Coût</th>
                            <th>Effectué par</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($entretiens as $e)
                        <tr>
                            <td class="ps-4 text-sm">{{ optional($e->date_entretien)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                @if ($e->voiture)
                                    <a href="{{ route('services-eleves.transport.voitures.show', $e->voiture) }}" class="text-decoration-none">
                                        <div class="fw-semibold">{{ $e->voiture->plaque }}</div>
                                        <div class="text-xs text-muted">{{ $e->voiture->marque }}</div>
                                    </a>
                                @else — @endif
                            </td>
                            <td><span class="badge-type">{{ $e->type_entretien }}</span></td>
                            <td class="num">{{ number_format((float) $e->kilometrage, 0, ',', ' ') }} km</td>
                            <td class="num grand">{{ number_format((float) $e->cout, 2, ',', ' ') }}</td>
                            <td class="text-sm">{{ $e->chauffeur?->nom_complet ?? '—' }}</td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-ent"
                                                    data-id="{{ $e->id }}"
                                                    data-voiture="{{ $e->voiture_id }}"
                                                    data-date="{{ optional($e->date_entretien)->toDateString() }}"
                                                    data-type="{{ $e->type_entretien }}"
                                                    data-cout="{{ $e->cout }}"
                                                    data-km="{{ $e->kilometrage }}"
                                                    data-observations="{{ $e->observations }}"
                                                    data-chauffeur="{{ $e->effectue_par }}"
                                                    data-bs-toggle="modal" data-bs-target="#entretienModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <x-actions.supprimer :action="route('services-eleves.transport.entretiens.destroy', $e)">Supprimer</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-tools">Aucun entretien enregistré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$entretiens->appends(request()->query())" /></div>

    @can('transport.gerer')
    <x-modale id="entretienModal" data-bs-backdrop="static" contenu-class="modal-ent" entete-class="modal-header-ent" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="entretien-form">@csrf
                    <input type="hidden" name="_method" id="ent-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="ent-title"><i class="fas fa-tools me-2"></i>Nouvel entretien</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Véhicule</x-libelle>
                <select name="voiture_id" id="e-voiture" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($voitures as $v)
                        <option value="{{ $v->id }}">{{ $v->plaque }} ({{ $v->marque }})</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Date</x-libelle>
                    <input type="date" name="date_entretien" id="e-date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Type</x-libelle>
                    <input type="text" name="type_entretien" id="e-type" class="form-control" maxlength="100" required placeholder="Vidange, révision, pneus...">
                </div>
                <div class="col-md-6">
                    <x-libelle>Coût</x-libelle>
                    <input type="number" name="cout" id="e-cout" step="0.01" min="0" class="form-control">
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Kilométrage</x-libelle>
                    <input type="number" name="kilometrage" id="e-km" min="0" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle>Effectué par</x-libelle>
                    <select name="effectue_par" id="e-chauffeur" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach ($chauffeurs as $c)
                            <option value="{{ $c->id }}">{{ $c->nom_complet }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <x-libelle>Observations</x-libelle>
                    <textarea name="observations" id="e-observations" rows="2" class="form-control" maxlength="2000"></textarea>
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
    const $form = $('#entretien-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelEntretien').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#ent-method').val('POST');
        $('#ent-title').html('<i class="fas fa-tools me-2"></i>Nouvel entretien');
    });

    $(document).on('click', '.btn-edit-ent', function () {
        currentId = $(this).data('id');
        $('#e-voiture').val($(this).data('voiture'));
        $('#e-date').val($(this).data('date'));
        $('#e-type').val($(this).data('type'));
        $('#e-cout').val($(this).data('cout'));
        $('#e-km').val($(this).data('km'));
        $('#e-chauffeur').val($(this).data('chauffeur') ?? '');
        $('#e-observations').val($(this).data('observations'));
        $('#ent-method').val('PUT');
        $('#ent-title').html('<i class="fas fa-pen me-2"></i>Modifier l\'entretien');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/entretiens') }}/${currentId}`
            : `{{ route('services-eleves.transport.entretiens.store') }}`;
        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#entretienModal').modal('hide');
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