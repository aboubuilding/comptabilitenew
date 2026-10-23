@extends('admin.layouts.app')

@section('title', 'Préparations repas')
@section('page_icon', 'fa-fire')
@section('page_title', 'Préparations de repas')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.cantine.inscriptions.index') }}">Cantine</a></li>
    <li class="breadcrumb-item active">Préparations</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.preparations.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('cantine.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvellePrep" data-bs-toggle="modal" data-bs-target="#preparationModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle préparation
        </x-bouton>
    @endcan
@endsection

@section('contenu')
<div class="container-fluid p-0">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-fire" couleur="blue" libelle="Préparations">{{ $stats['nb'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-utensils" couleur="green" libelle="Parts servies">{{ $stats['parts'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="gold" libelle="Coût total">{{ number_format($stats['cout_total'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-chart-line" couleur="red" libelle="Coût moyen / part">{{ number_format($stats['cout_moyen_par_part'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="menu_id" class="form-select filtre-input">
                        <option value="">— Menu —</option>
                        @foreach ($menus as $m)
                            <option value="{{ $m->id }}" @selected(($filters['menu_id'] ?? '') == $m->id)>
                                {{ $m->libelle }} ({{ optional($m->date_service)->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
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
            <x-tableau class="table-insc align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Menu</th>
                            <th>Magasin</th>
                            <th class="text-end">Parts</th>
                            <th class="text-end">Coût réel</th>
                            <th class="text-end">Coût / part</th>
                            <th>Responsable</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($preparations as $p)
                        <tr>
                            <td class="ps-4 text-sm">{{ optional($p->date_preparation)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                <div class="fw-semibold">{{ $p->menu?->libelle ?? '—' }}</div>
                                <div class="text-xs text-muted">{{ $p->menu?->type_repas?->label() }}</div>
                            </td>
                            <td class="text-sm">{{ $p->magasin?->libelle ?? '—' }}</td>
                            <td class="num grand">{{ $p->nombre_parts }}</td>
                            <td class="num">{{ number_format((float) $p->cout_reel, 0, ',', ' ') }}</td>
                            <td class="num">
                                {{ $p->nombre_parts > 0 ? number_format((float) $p->cout_reel / $p->nombre_parts, 0, ',', ' ') : '—' }}
                            </td>
                            <td class="text-sm text-muted">{{ $p->responsable?->name ?? '—' }}</td>
                            <td>
                                @if ($p->stock_decremente)
                                    <x-badge class="actif" style="background:#e3f7ec;color:var(--eim-success);" point>Déduit</x-badge>
                                @else
                                    <x-badge class="abandonne" point>Non déduit</x-badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="8" icone="fas fa-fire">Aucune préparation enregistrée.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$preparations->appends(request()->query())" /></div>

    @can('cantine.gerer')
    <x-modale id="preparationModal" data-bs-backdrop="static" contenu-class="modal-cantine" entete-class="modal-header-cantine" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="preparation-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-fire me-2"></i>Nouvelle préparation</x-slot:titre>
        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
            <i class="fas fa-info-circle me-1"></i>
            Le stock sera automatiquement décrémenté selon les produits du menu et le nombre de parts servies.
        </div>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Menu</x-libelle>
                <select name="menu_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($menus as $m)
                        <option value="{{ $m->id }}">{{ $m->libelle }} ({{ optional($m->date_service)->format('d/m/Y') }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <x-libelle requis>Magasin source</x-libelle>
                <select name="magasin_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($magasins as $m)
                        <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Date</x-libelle>
                    <input type="date" name="date_preparation" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Nombre de parts</x-libelle>
                    <input type="number" name="nombre_parts" min="1" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle>Coût réel (laisser vide = calcul auto)</x-libelle>
                    <input type="number" name="cout_reel" step="0.01" min="0" class="form-control">
                </div>
                <div class="col-md-6">
                    <x-libelle>Observations</x-libelle>
                    <input type="text" name="observations" class="form-control" maxlength="2000">
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
    $('#preparation-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.cantine.preparations.store") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#preparationModal').modal('hide');
                window.showToastThenReload(res.message || 'Préparation enregistrée.', 'success');
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