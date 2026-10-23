@extends('admin.layouts.app')

@section('title', 'Inscriptions cantine')
@section('page_icon', 'fa-utensils')
@section('page_title', 'Cantine scolaire')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item active">Cantine</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.inscriptions.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.menus.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-book-open me-1"></i> Menus
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.preparations.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-fire me-1"></i> Préparations
    </x-bouton>
    @can('cantine.inscrire')
        <x-bouton variante="nouveau" type="button" id="btnNouvelleInscription" data-bs-toggle="modal" data-bs-target="#inscriptionModal">
            <i class="fas fa-plus-circle me-1"></i> Inscrire un élève
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .cantine-page { --c-bg-soft:var(--eim-cream); --c-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--c-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    /* Menu du jour */
    .menu-jour-card {
        background: linear-gradient(135deg, var(--eim-success), #2ecc71);
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        box-shadow: 0 8px 24px rgba(28,122,77,.18);
        margin-bottom:1.25rem;
    }
    .menu-jour-title { font-size:.85rem; opacity:.85; text-transform:uppercase; letter-spacing:1px; font-weight:600; }
    .menu-jour-libelle { font-size:1.3rem; font-weight:700; margin:4px 0 8px; }
    .menu-jour-produits { font-size:.85rem; opacity:.9; }
    .menu-jour-produits .item { display:inline-block; background:rgba(255,255,255,.18); padding:2px 10px; border-radius:999px; margin-right:6px; margin-top:4px; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--c-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--c-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--c-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--c-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--c-border); }

    .table-insc thead tr { background:var(--c-bg-soft); }
    .table-insc thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--c-border); white-space:nowrap; }
    .table-insc tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--c-border); font-size:.9rem; vertical-align:middle; }
    .table-insc tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-statut.abandonne { background:#eceff2; color:var(--eim-ink-soft); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-cantine { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-cantine { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--c-bg-soft); border:1px solid var(--c-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--c-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--c-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="cantine-page">

    {{-- Menu du jour --}}
    @if ($menuDuJour->isNotEmpty())
        <div class="menu-jour-card">
            <div class="menu-jour-title"><i class="fas fa-utensils me-1"></i> Menu du jour — {{ now()->translatedFormat('l d F Y') }}</div>
            @foreach ($menuDuJour as $menu)
                <div class="menu-jour-libelle">{{ $menu->libelle }}</div>
                @if ($menu->menuProduits->isNotEmpty())
                    <div class="menu-jour-produits">
                        @foreach ($menu->menuProduits as $mp)
                            <span class="item">
                                {{ $mp->produit?->libelle }}
                                <strong>{{ number_format((float) $mp->quantite, 2, ',', ' ') }}</strong>
                                {{ $mp->produit?->unite_stock }}
                            </span>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-users" couleur="green" libelle="Élèves inscrits">{{ $compteurs['total_actifs'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-coins" couleur="gold" libelle="Revenus mensuels">{{ number_format($compteurs['revenus_mensuels'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-calendar-alt" couleur="blue" libelle="Total annuel attendu">{{ number_format($compteurs['revenus_annuels'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-book-open" couleur="blue" libelle="Menus cette semaine">{{ $menuSemaine->count() }}</x-stat>
        </div>
    </div>

    {{-- Inscriptions --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <x-recherche name="search" placeholder="Nom, matricule..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut'] ?? '') === (string) $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.inscriptions.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-insc align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Élève</th>
                            <th>Classe</th>
                            <th>Période</th>
                            <th class="text-end">Mensuel</th>
                            <th class="text-end">Total dû</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($inscriptions as $insc)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">
                                    {{ $insc->inscription?->eleve?->nom }} {{ $insc->inscription?->eleve?->prenom }}
                                </div>
                                <div class="text-xs text-muted">{{ $insc->inscription?->eleve?->matricule }}</div>
                            </td>
                            <td class="text-sm">{{ $insc->inscription?->classe?->libelle ?? '—' }}</td>
                            <td class="text-sm">
                                {{ optional($insc->date_debut)->format('d/m/Y') }}
                                @if ($insc->date_fin)
                                    → {{ $insc->date_fin->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="num">{{ number_format((float) $insc->montant_mensuel, 0, ',', ' ') }}</td>
                            <td class="num grand">{{ number_format((float) $insc->montant_total_du, 0, ',', ' ') }}</td>
                            <td>
                                <x-badge class="{{ $insc->statut?->css() }}" point>{{ $insc->statut?->label() }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @if ($insc->isActif())
                                            @can('cantine.gerer')
                                                <li>
                                                    <button class="dropdown-item text-danger btn-abandonner"
                                                            data-id="{{ $insc->id }}"
                                                            data-eleve="{{ $insc->inscription?->eleve?->nom }} {{ $insc->inscription?->eleve?->prenom }}"
                                                            data-bs-toggle="modal" data-bs-target="#abandonModal">
                                                        <i class="fas fa-times"></i> Abandonner
                                                    </button>
                                                </li>
                                            @endcan
                                        @else
                                            <li>
                                                <span class="dropdown-item text-muted small" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i>
                                                    Abandonné le {{ optional($insc->date_abandon)->format('d/m/Y') }}
                                                </span>
                                            </li>
                                        @endif                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-utensils">Aucune inscription cantine.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$inscriptions->appends(request()->query())" /></div>

    {{-- Modal souscription --}}
    @can('cantine.inscrire')
    <x-modale id="inscriptionModal" data-bs-backdrop="static" contenu-class="modal-cantine" entete-class="modal-header-cantine" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="inscription-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-utensils me-2"></i>Inscrire un élève</x-slot:titre>
        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
            <i class="fas fa-info-circle me-1"></i>
            Cette action crée l'engagement <strong>indépendamment du paiement</strong>.
            Le montant dû apparaîtra dans la situation financière de l'élève.
        </div>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Élève</x-libelle>
                @include('admin.partials.select-eleve')
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Date de début</x-libelle>
                    <input type="date" name="date_debut" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Montant mensuel</x-libelle>
                    <input type="number" name="montant_mensuel" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Nombre de mois</x-libelle>
                    <input type="number" name="nombre_mois" min="1" max="12" value="9" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle>Frais lié (optionnel)</x-libelle>
                    <select name="frais_ecole_id" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach ($frais as $f)
                            <option value="{{ $f->id }}">{{ $f->libelle }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Inscrire</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modal abandon --}}
    @can('cantine.gerer')
    <x-modale id="abandonModal" aria-hidden="true" contenu-class="modal-cantine" entete-style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="abandon-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-exclamation-triangle me-2"></i>Abandonner l'abonnement cantine</x-slot:titre>
        <p>Abandonner l'abonnement de <strong id="abandon-eleve">—</strong> ?</p>
        <x-section>
            <x-libelle class="small fw-semibold" requis>Motif</x-libelle>
            <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer</button>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#inscription-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.cantine.inscriptions.store") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#inscriptionModal').modal('hide');
                window.showToastThenReload(res.message || 'Inscription enregistrée.', 'success');
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

    $('.btn-abandonner').on('click', function () {
        $('#abandon-eleve').text($(this).data('eleve'));
        $('#abandon-form').attr('action', `{{ url('services-eleves/cantine/inscriptions') }}/${$(this).data('id')}/abandonner`);
    });

    $('#abandon-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#abandonModal').modal('hide');
                window.showToastThenReload(res.message || 'Abandon enregistré.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
            }
        });
    });
});
</script>
@endpush