@extends('admin.layouts.app')

@section('title', 'Chauffeurs')
@section('page_icon', 'fa-user-tie')
@section('page_title', 'Chauffeurs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Chauffeurs</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.voitures.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-bus me-1"></i> Véhicules
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauChauffeur" data-bs-toggle="modal" data-bs-target="#chauffeurModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau chauffeur
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .ch-page { --ch-bg-soft:var(--eim-cream); --ch-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--ch-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--ch-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--ch-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--ch-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--ch-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--ch-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-chauffeurs thead tr { background:var(--ch-bg-soft); }
    .table-chauffeurs thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--ch-border); white-space:nowrap; }
    .table-chauffeurs tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--ch-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-chauffeurs tbody tr:hover { background:var(--eim-hover); }

    .chauffeur-avatar { width:36px; height:36px; border-radius:50%; background:linear-gradient(145deg,var(--school-gold-soft),var(--school-gold)); color:var(--school-red-deep); font-size:.8rem; font-weight:800; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
    .chauffeur-chip { display:inline-flex; align-items:center; gap:8px; }
    .chauffeur-nom { font-weight:700; color:var(--school-ink); }
    .chauffeur-role { font-size:.75rem; color:var(--school-muted); }


    .permis-warning {
        display:inline-flex; align-items:center; gap:4px;
        background:#fdf4e0; color:var(--school-gold-dark);
        padding:.2em .6em; border-radius:6px; font-size:.7rem; font-weight:600;
    }
    .permis-expire {
        display:inline-flex; align-items:center; gap:4px;
        background:#fdecea; color:var(--school-red);
        padding:.2em .6em; border-radius:6px; font-size:.7rem; font-weight:700;
    }

    .alerte-box {
        background:#f0f6ff; border-left:3px solid #2563eb;
        border-radius:8px; padding:.85rem 1rem; margin-bottom:1rem;
        font-size:.85rem; color:#1e3a8a;
    }
    .alerte-box i { color:#2563eb; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-ch { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-ch { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--ch-bg-soft); border:1px solid var(--ch-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--ch-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--ch-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="ch-page">

    @if ($permisExpirant->isNotEmpty())
        <div class="alerte-box">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>{{ $permisExpirant->count() }} permis</strong> expirent dans moins de 60 jours :
            @foreach ($permisExpirant as $c)
                <x-badge class="inactif ms-1" style="background:#fff;border:1px solid #2563eb;color:#2563eb;">
                    {{ $c->nom_complet }} — {{ $c->date_validite_permis->format('d/m/Y') }}
                </x-badge>
            @endforeach
        </div>
    @endif

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <x-recherche name="search" placeholder="Nom, permis, téléphone..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        <option value="1" @selected(($filters['statut'] ?? '') == 1)>Actif</option>
                        <option value="0" @selected(($filters['statut'] ?? '') === '0')>Inactif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
                <div class="col-md-2 text-end">
                    <x-compteur>{{ $chauffeurs->total() }} chauffeur(s)</x-compteur>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-chauffeurs align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Chauffeur</th>
                            <th>Permis</th>
                            <th>Contact</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($chauffeurs as $c)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('services-eleves.transport.chauffeurs.show', $c) }}" class="text-decoration-none">
                                    <div class="chauffeur-chip">
                                        <div class="chauffeur-avatar">
                                            {{ strtoupper(substr($c->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($c->nom ?? '', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="chauffeur-nom">{{ $c->nom_complet }}</div>
                                            <div class="chauffeur-role">Chauffeur</div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td class="text-sm">
                                @if ($c->permis_conduire)
                                    <div style="font-family:monospace;">{{ $c->permis_conduire }}</div>
                                @endif
                                @if ($c->date_validite_permis)
                                    @php
                                        $joursRestants = now()->diffInDays($c->date_validite_permis, false);
                                    @endphp
                                    @if ($joursRestants < 0)
                                        <span class="permis-expire">
                                            <i class="fas fa-times-circle"></i> Expiré ({{ $c->date_validite_permis->format('d/m/Y') }})
                                        </span>
                                    @elseif ($joursRestants <= 60)
                                        <span class="permis-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Expire dans {{ (int) $joursRestants }} j
                                        </span>
                                    @else
                                        <div class="text-xs text-muted">Valide jusqu'au {{ $c->date_validite_permis->format('d/m/Y') }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($c->telephone)
                                    <div><i class="fas fa-phone me-1 text-muted"></i>{{ $c->telephone }}</div>
                                @endif
                                @if ($c->email)
                                    <div class="text-xs text-muted"><i class="fas fa-envelope me-1"></i>{{ $c->email }}</div>
                                @endif
                            </td>
                            <td>
                                <x-badge class="{{ $c->isActif() ? 'actif' : 'inactif' }}" point>{{ $c->isActif() ? 'Actif' : 'Inactif' }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('services-eleves.transport.chauffeurs.show', $c) }}">
                                                <i class="fas fa-eye"></i> Voir détail
                                            </a>
                                        </li>
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-chauffeur"
                                                    data-id="{{ $c->id }}"
                                                    data-nom="{{ $c->nom }}"
                                                    data-prenom="{{ $c->prenom }}"
                                                    data-permis="{{ $c->permis_conduire }}"
                                                    data-validite="{{ optional($c->date_validite_permis)->toDateString() }}"
                                                    data-telephone="{{ $c->telephone }}"
                                                    data-email="{{ $c->email }}"
                                                    data-adresse="{{ $c->adresse }}"
                                                    data-statut="{{ $c->statut }}"
                                                    data-bs-toggle="modal" data-bs-target="#chauffeurModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <x-actions.supprimer :action="route('services-eleves.transport.chauffeurs.destroy', $c)" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-user-tie">Aucun chauffeur enregistré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$chauffeurs->appends(request()->query())" /></div>

    @can('transport.gerer')
    <x-modale id="chauffeurModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-ch" entete-class="modal-header-ch" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="chauffeur-form">@csrf
                    <input type="hidden" name="_method" id="chauffeur-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="chauffeur-title"><i class="fas fa-user-tie me-2"></i>Nouveau chauffeur</x-slot:titre>
        <x-section titre="Identité" icone="fas fa-user" class="mb-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Nom</x-libelle>
                    <input type="text" name="nom" id="c-nom" class="form-control" maxlength="100" required>
                    <div class="text-danger text-xs mt-1" id="nom-error"></div>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Prénom</x-libelle>
                    <input type="text" name="prenom" id="c-prenom" class="form-control" maxlength="100" required>
                    <div class="text-danger text-xs mt-1" id="prenom-error"></div>
                </div>
            </div>
        </x-section>

        <x-section titre="Permis de conduire" icone="fas fa-id-card" class="mb-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle>Numéro de permis</x-libelle>
                    <input type="text" name="permis_conduire" id="c-permis" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6">
                    <x-libelle>Date de validité</x-libelle>
                    <input type="date" name="date_validite_permis" id="c-validite" class="form-control">
                </div>
            </div>
        </x-section>

        <x-section titre="Contact" icone="fas fa-address-book">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle>Téléphone</x-libelle>
                    <input type="text" name="telephone" id="c-telephone" class="form-control" maxlength="30">
                </div>
                <div class="col-md-6">
                    <x-libelle>Email</x-libelle>
                    <input type="email" name="email" id="c-email" class="form-control" maxlength="150">
                </div>
                <div class="col-12">
                    <x-libelle>Adresse</x-libelle>
                    <textarea name="adresse" id="c-adresse" rows="2" class="form-control" maxlength="1000"></textarea>
                </div>
                <div class="col-md-6">
                    <x-libelle>Statut</x-libelle>
                    <select name="statut" id="c-statut" class="form-select">
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
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
    const $form = $('#chauffeur-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouveauChauffeur').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#chauffeur-method').val('POST');
        $('#chauffeur-title').html('<i class="fas fa-user-tie me-2"></i>Nouveau chauffeur');
    });

    $(document).on('click', '.btn-edit-chauffeur', function () {
        currentId = $(this).data('id');
        $('#c-nom').val($(this).data('nom'));
        $('#c-prenom').val($(this).data('prenom'));
        $('#c-permis').val($(this).data('permis'));
        $('#c-validite').val($(this).data('validite'));
        $('#c-telephone').val($(this).data('telephone'));
        $('#c-email').val($(this).data('email'));
        $('#c-adresse').val($(this).data('adresse'));
        $('#c-statut').val($(this).data('statut'));
        $('#chauffeur-method').val('PUT');
        $('#chauffeur-title').html('<i class="fas fa-pen me-2"></i>Modifier le chauffeur');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/chauffeurs') }}/${currentId}`
            : `{{ route('services-eleves.transport.chauffeurs.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#chauffeurModal').modal('hide');
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