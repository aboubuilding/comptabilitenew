@extends('admin.layouts.app')

@section('title', 'Activités parascolaires')
@section('page_icon', 'fa-futbol')
@section('page_title', 'Activités parascolaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item active">Activités &amp; Événements</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.activites.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.inscriptions.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-users me-1"></i> Inscriptions
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.evenements.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-calendar-check me-1"></i> Événements
    </x-bouton>
    @can('activites.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelleActivite" data-bs-toggle="modal" data-bs-target="#activiteModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle activité
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .act-page { --a-bg-soft:var(--eim-cream); --a-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--a-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--a-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--a-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--a-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--a-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--a-border); }

    .table-activites thead tr { background:var(--a-bg-soft); }
    .table-activites thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--a-border); white-space:nowrap; }
    .table-activites tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--a-border); font-size:.9rem; vertical-align:middle; }
    .table-activites tbody tr:hover { background:var(--eim-hover); }

    .activite-libelle { font-weight:700; color:var(--school-ink); display:block; }
    .activite-sub { font-size:.72rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-type { display:inline-flex; padding:.3em .8em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-type.sportive      { background:#e3f7ec; color:var(--eim-success); }
    .badge-type.culturelle    { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-type.artistique    { background:#fce4ec; color:#c2185b; }
    .badge-type.scientifique  { background:#e0ecfc; color:#2563eb; }
    .badge-type.linguistique  { background:#f3e5f5; color:#7b1fa2; }
    .badge-type.technologique { background:#e0f7fa; color:#00838f; }
    .badge-type.sociale       { background:#fff3e0; color:#e65100; }
    .badge-type.autre         { background:#eceff2; color:var(--eim-ink-soft); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-act { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-act { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--a-bg-soft); border:1px solid var(--a-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--a-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--a-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="act-page">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-futbol" couleur="blue" libelle="Activités totales">{{ $stats['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-running" couleur="green" libelle="Sportives">{{ $stats['sportives'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-palette" couleur="gold" libelle="Culturelles">{{ $stats['culturelles'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-star" couleur="red" libelle="Autres">{{ $stats['autres'] }}</x-stat>
        </div>
    </div>

    {{-- Tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <x-recherche name="search" placeholder="Rechercher une activité..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="niveau_id" class="form-select filtre-input">
                        <option value="">— Niveau —</option>
                        @foreach ($niveaux as $n)
                            <option value="{{ $n->id }}" @selected(($filters['niveau_id'] ?? '') == $n->id)>{{ $n->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.activites.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-activites align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Activité</th>
                            <th>Type</th>
                            <th>Niveau</th>
                            <th>Encadreur</th>
                            <th class="text-end">Montant</th>
                            <th class="text-center">Inscrits</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($activites as $activite)
                        <tr>
                            <td class="ps-4">
                                <span class="activite-libelle">{{ $activite->libelle }}</span>
                                @if ($activite->description)
                                    <span class="activite-sub">{{ \Str::limit($activite->description, 70) }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($activite->type)
                                    <span class="badge-type {{ $activite->type->css() }}">
                                        {{ $activite->type->label() }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-sm">{{ $activite->niveau?->libelle ?? 'Tous niveaux' }}</td>
                            <td class="text-sm">
                                {{ $activite->encadreur ?? '—' }}
                                @if ($activite->contact_encadreur)
                                    <div class="text-xs text-muted">{{ $activite->contact_encadreur }}</div>
                                @endif
                            </td>
                            <td class="num grand">{{ number_format((float) $activite->montant, 0, ',', ' ') }}</td>
                            <td class="text-center">
                                <x-compteur>{{ $activite->inscriptions_count ?? 0 }}</x-compteur>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('activites.inscrire')
                                            <li>
                                                <button class="dropdown-item btn-souscrire"
                                                        data-id="{{ $activite->id }}"
                                                        data-libelle="{{ $activite->libelle }}"
                                                        data-montant="{{ $activite->montant }}"
                                                        data-bs-toggle="modal" data-bs-target="#souscriptionModal">
                                                    <i class="fas fa-user-plus"></i> Inscrire un élève
                                                </button>
                                            </li>
                                        @endcan
                                        @can('activites.gerer')
                                            <li>
                                                <button class="dropdown-item btn-edit-activite"
                                                        data-id="{{ $activite->id }}"
                                                        data-libelle="{{ $activite->libelle }}"
                                                        data-description="{{ $activite->description }}"
                                                        data-montant="{{ $activite->montant }}"
                                                        data-type="{{ $activite->type?->value }}"
                                                        data-encadreur="{{ $activite->encadreur }}"
                                                        data-contact="{{ $activite->contact_encadreur }}"
                                                        data-niveau="{{ $activite->niveau_id }}"
                                                        data-bs-toggle="modal" data-bs-target="#activiteModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-actions.supprimer :action="route('services-eleves.activites-evenements.activites.destroy', $activite)" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-futbol">Aucune activité enregistrée.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$activites->appends(request()->query())" /></div>

    {{-- Modale activité --}}
    @can('activites.gerer')
    <x-modale id="activiteModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-act" entete-class="modal-header-act" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="activite-form">@csrf
                    <input type="hidden" name="_method" id="act-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="act-title"><i class="fas fa-futbol me-2"></i>Nouvelle activité</x-slot:titre>
        <x-section>
            <div class="row g-3">
                <div class="col-md-8">
                    <x-libelle requis>Libellé</x-libelle>
                    <input type="text" name="libelle" id="ac-libelle" class="form-control" maxlength="200" required>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Montant</x-libelle>
                    <input type="number" name="montant" id="ac-montant" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <x-libelle>Type</x-libelle>
                    <select name="type" id="ac-type" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Niveau</x-libelle>
                    <select name="niveau_id" id="ac-niveau" class="form-select">
                        <option value="">— Tous niveaux —</option>
                        @foreach ($niveaux as $n)
                            <option value="{{ $n->id }}">{{ $n->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Encadreur</x-libelle>
                    <input type="text" name="encadreur" id="ac-encadreur" class="form-control" maxlength="150">
                </div>
                <div class="col-md-6">
                    <x-libelle>Contact encadreur</x-libelle>
                    <input type="text" name="contact_encadreur" id="ac-contact" class="form-control" maxlength="50">
                </div>
                <div class="col-12">
                    <x-libelle>Description</x-libelle>
                    <textarea name="description" id="ac-description" rows="2" class="form-control" maxlength="2000"></textarea>
                </div>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modale souscription --}}
    @can('activites.inscrire')
    <x-modale id="souscriptionModal" data-bs-backdrop="static" contenu-class="modal-act" entete-class="modal-header-act" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="souscription-form">@csrf
                    <input type="hidden" name="activite_id" id="sc-activite-id"></x-slot:formulaire>
        <x-slot:titre><i class="fas fa-user-plus me-2"></i>Inscrire un élève</x-slot:titre>
        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
            <i class="fas fa-info-circle me-1"></i>
            Cette action crée l'engagement <strong>indépendamment du paiement</strong>.
            Le montant dû apparaîtra dans la situation financière de l'élève.
        </div>
        <p class="mb-3">Activité : <strong id="sc-activite-libelle">—</strong></p>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Élève</x-libelle>
                @include('admin.partials.select-eleve', ['name' => 'eleve_id', 'valeur' => 'eleve'])
            </div>
            <div class="mb-3">
                <x-libelle>Montant dû (laisser vide = montant activité)</x-libelle>
                <input type="number" name="montant_du" id="sc-montant" step="0.01" min="0" class="form-control">
            </div>
            <div>
                <x-libelle>Date d'inscription</x-libelle>
                <input type="date" name="date_inscription" class="form-control" value="{{ now()->toDateString() }}">
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Inscrire</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    // ========== FORMULAIRE ACTIVITÉ ==========
    const $form = $('#activite-form');
    if ($form.length) {
        let currentId = null;
        $('#btnNouvelleActivite').on('click', function () {
            currentId = null;
            $form[0].reset();
            $('#act-method').val('POST');
            $('#act-title').html('<i class="fas fa-futbol me-2"></i>Nouvelle activité');
        });
        $(document).on('click', '.btn-edit-activite', function () {
            currentId = $(this).data('id');
            $('#ac-libelle').val($(this).data('libelle'));
            $('#ac-description').val($(this).data('description'));
            $('#ac-montant').val($(this).data('montant'));
            $('#ac-type').val($(this).data('type'));
            $('#ac-encadreur').val($(this).data('encadreur'));
            $('#ac-contact').val($(this).data('contact'));
            $('#ac-niveau').val($(this).data('niveau') ?? '');
            $('#act-method').val('PUT');
            $('#act-title').html('<i class="fas fa-pen me-2"></i>Modifier l\'activité');
        });
        $form.on('submit', function (e) {
            e.preventDefault();
            const url = currentId
                ? `{{ url('services-eleves/activites-evenements/activites') }}/${currentId}`
                : `{{ route('services-eleves.activites-evenements.activites.store') }}`;
            $.ajax({
                url, method: 'POST', data: $form.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#activiteModal').modal('hide');
                    window.showToastThenReload(res.message || 'Enregistré.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                    } else {
                        window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
                    }
                }
            });
        });
    }

    // ========== SOUSCRIPTION ==========
    $('.btn-souscrire').on('click', function () {
        $('#sc-activite-id').val($(this).data('id'));
        $('#sc-activite-libelle').text($(this).data('libelle'));
        $('#sc-montant').attr('placeholder', $(this).data('montant'));
    });

    $('#souscription-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.activites-evenements.activites.souscrire") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#souscriptionModal').modal('hide');
                window.showToastThenReload(res.message || 'Élève inscrit.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
                }
            }
        });
    });
});
</script>
@endpush