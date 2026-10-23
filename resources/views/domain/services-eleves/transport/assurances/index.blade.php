@extends('admin.layouts.app')

@section('title', 'Assurances véhicules')
@section('page_icon', 'fa-shield-alt')
@section('page_title', 'Assurances des véhicules')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Assurances</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.assurances.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelleAssurance" data-bs-toggle="modal" data-bs-target="#assuranceModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle assurance
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .as-page { --a-border:var(--eim-border); --a-bg-soft:var(--eim-cream); }
    .stat-card { background:#fff; border:1px solid var(--a-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .alerte-box {
        background:#fdf4e0; border-left:3px solid var(--school-gold-dark);
        border-radius:8px; padding:.85rem 1rem; margin-bottom:1rem;
        font-size:.85rem; color:#7a5a00;
    }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--a-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--a-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--a-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--a-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--a-border); }

    .table-as thead tr { background:var(--a-bg-soft); }
    .table-as thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--a-border); white-space:nowrap; }
    .table-as tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--a-border); font-size:.9rem; vertical-align:middle; }
    .table-as tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-statut.active   { background:#e3f7ec; color:var(--eim-success); }
    .badge-statut.bientot  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.expiree  { background:#fdecea; color:var(--school-red); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-as { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-as { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--a-bg-soft); border:1px solid var(--a-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--a-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--a-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="as-page">

    @if ($bientot->isNotEmpty())
        <div class="alerte-box">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>{{ $bientot->count() }} assurance(s)</strong> expirent dans les 30 prochains jours :
            @foreach ($bientot as $a)
                <x-badge class="bientot ms-1" style="background:#fff;border:1px solid var(--school-gold-dark);">
                    {{ $a->voiture?->plaque }} — {{ $a->date_fin->format('d/m/Y') }}
                </x-badge>
            @endforeach
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-check-circle" couleur="green" libelle="Assurances actives">{{ $stats['active'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-clock" couleur="gold" libelle="Expirent bientôt">{{ $stats['bientot'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-times-circle" couleur="red" libelle="Expirées">{{ $stats['expirees'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="green" libelle="Primes actives">{{ number_format($stats['prime_totale'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Compagnie, contrat..." value="{{ $filters['search'] ?? '' }}" />
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
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        <option value="active"  @selected(($filters['statut'] ?? '') === 'active')>Actives</option>
                        <option value="bientot" @selected(($filters['statut'] ?? '') === 'bientot')>Expirent bientôt</option>
                        <option value="expiree" @selected(($filters['statut'] ?? '') === 'expiree')>Expirées</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-as align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Véhicule</th>
                            <th>Compagnie</th>
                            <th>N° contrat</th>
                            <th>Type</th>
                            <th>Période</th>
                            <th class="text-end">Prime</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($assurances as $a)
                        @php
                            $joursRestants = now()->diffInDays($a->date_fin, false);
                            if ($joursRestants < 0) { $statut = 'expiree'; $label = 'Expirée'; }
                            elseif ($joursRestants <= 30) { $statut = 'bientot'; $label = 'Expire dans ' . (int) $joursRestants . ' j'; }
                            else { $statut = 'active'; $label = 'Active'; }
                        @endphp
                        <tr>
                            <td class="ps-4">
                                @if ($a->voiture)
                                    <a href="{{ route('services-eleves.transport.voitures.show', $a->voiture) }}" class="text-decoration-none">
                                        <div class="fw-semibold">{{ $a->voiture->plaque }}</div>
                                        <div class="text-xs text-muted">{{ $a->voiture->marque }}</div>
                                    </a>
                                @endif
                            </td>
                            <td class="text-sm">{{ $a->compagnie_assurance }}</td>
                            <td class="text-sm" style="font-family:monospace;">{{ $a->numero_contrat }}</td>
                            <td class="text-sm">{{ $a->type_assurance }}</td>
                            <td class="text-sm">
                                {{ $a->date_debut->format('d/m/Y') }} → {{ $a->date_fin->format('d/m/Y') }}
                            </td>
                            <td class="num grand">{{ number_format((float) $a->prime, 2, ',', ' ') }}</td>
                            <td>
                                <x-badge class="{{ $statut }}" point>{{ $label }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-as"
                                                    data-id="{{ $a->id }}"
                                                    data-voiture="{{ $a->voiture_id }}"
                                                    data-compagnie="{{ $a->compagnie_assurance }}"
                                                    data-contrat="{{ $a->numero_contrat }}"
                                                    data-debut="{{ $a->date_debut->toDateString() }}"
                                                    data-fin="{{ $a->date_fin->toDateString() }}"
                                                    data-prime="{{ $a->prime }}"
                                                    data-type="{{ $a->type_assurance }}"
                                                    data-bs-toggle="modal" data-bs-target="#assuranceModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <x-actions.supprimer :action="route('services-eleves.transport.assurances.destroy', $a)">Supprimer</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="8" icone="fas fa-shield-alt">Aucune assurance enregistrée.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$assurances->appends(request()->query())" /></div>

    @can('transport.gerer')
    <x-modale id="assuranceModal" data-bs-backdrop="static" contenu-class="modal-as" entete-class="modal-header-as" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="assurance-form">@csrf
                    <input type="hidden" name="_method" id="as-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="as-title"><i class="fas fa-shield-alt me-2"></i>Nouvelle assurance</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Véhicule</x-libelle>
                <select name="voiture_id" id="as-voiture" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($voitures as $v)
                        <option value="{{ $v->id }}">{{ $v->plaque }} ({{ $v->marque }})</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Compagnie</x-libelle>
                    <input type="text" name="compagnie_assurance" id="as-compagnie" class="form-control" maxlength="150" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>N° contrat</x-libelle>
                    <input type="text" name="numero_contrat" id="as-contrat" class="form-control" maxlength="100" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Date début</x-libelle>
                    <input type="date" name="date_debut" id="as-debut" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Date fin</x-libelle>
                    <input type="date" name="date_fin" id="as-fin" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Prime</x-libelle>
                    <input type="number" name="prime" id="as-prime" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Type</x-libelle>
                    <input type="text" name="type_assurance" id="as-type" class="form-control" maxlength="100" required placeholder="Tiers, tous risques...">
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
    const $form = $('#assurance-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelleAssurance').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#as-method').val('POST');
        $('#as-title').html('<i class="fas fa-shield-alt me-2"></i>Nouvelle assurance');
    });

    $(document).on('click', '.btn-edit-as', function () {
        currentId = $(this).data('id');
        $('#as-voiture').val($(this).data('voiture'));
        $('#as-compagnie').val($(this).data('compagnie'));
        $('#as-contrat').val($(this).data('contrat'));
        $('#as-debut').val($(this).data('debut'));
        $('#as-fin').val($(this).data('fin'));
        $('#as-prime').val($(this).data('prime'));
        $('#as-type').val($(this).data('type'));
        $('#as-method').val('PUT');
        $('#as-title').html('<i class="fas fa-pen me-2"></i>Modifier l\'assurance');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/assurances') }}/${currentId}`
            : `{{ route('services-eleves.transport.assurances.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#assuranceModal').modal('hide');
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