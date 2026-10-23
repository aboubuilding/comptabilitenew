@extends('admin.layouts.app')

@section('title', 'Inscriptions aux activités')
@section('page_icon', 'fa-user-check')
@section('page_title', 'Inscriptions aux activités')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.activites-evenements.activites.index') }}">Activités</a></li>
    <li class="breadcrumb-item active">Inscriptions</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.activites.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-futbol me-1"></i> Activités
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.evenements.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-calendar-check me-1"></i> Événements
    </x-bouton>
    @can('activites.inscrire')
        <x-bouton variante="nouveau" href="{{ route('services-eleves.activites-evenements.activites.index') }}#inscrire">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle inscription
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .insc-page { --i-bg-soft:var(--eim-cream); --i-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--i-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--i-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--i-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--i-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--i-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--i-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--i-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-insc-act thead tr { background:var(--i-bg-soft); }
    .table-insc-act thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--i-border); white-space:nowrap; }
    .table-insc-act tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--i-border); font-size:.9rem; vertical-align:middle; }
    .table-insc-act tbody tr:hover { background:var(--eim-hover); }

    .eleve-nom { font-weight:700; color:var(--school-ink); display:block; }
    .eleve-mat { font-size:.72rem; color:var(--school-muted); font-family:monospace; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-statut.confirmee { background:#e3f7ec; color:var(--eim-success); }
    .badge-statut.annulee   { background:#eceff2; color:var(--eim-ink-soft); }

    .badge-activite { display:inline-flex; align-items:center; padding:.2em .7em; border-radius:6px; font-size:.72rem; font-weight:600; background:var(--i-bg-soft); color:var(--school-red); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="insc-page">

    {{-- Statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-users" couleur="blue" libelle="Inscriptions totales">{{ $stats['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-hourglass-half" couleur="gold" libelle="En attente de paiement">{{ $stats['en_attente'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-check-circle" couleur="green" libelle="Confirmées">{{ $stats['confirmees'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="red" libelle="Montant dû cumulé">{{ number_format($stats['montant_du'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    {{-- Filtres + tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Élève, matricule..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="activite_id" class="form-select filtre-input">
                        <option value="">— Activité —</option>
                        @foreach ($activites as $a)
                            <option value="{{ $a->id }}" @selected(($filters['activite_id'] ?? '') == $a->id)>
                                {{ \Str::limit($a->libelle, 40) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-insc-act align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Élève</th>
                            <th>Activité</th>
                            <th>Type</th>
                            <th class="text-end">Montant dû</th>
                            <th>Statut</th>
                            <th>Date inscription</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($inscriptions as $insc)
                        <tr>
                            <td class="ps-4">
                                <span class="eleve-nom">
                                    {{ $insc->eleve?->nom }} {{ $insc->eleve?->prenom }}
                                </span>
                                <span class="eleve-mat">{{ $insc->eleve?->matricule }}</span>
                            </td>
                            <td class="text-sm">
                                <span class="badge-activite">{{ $insc->activite?->libelle ?? '—' }}</span>
                            </td>
                            <td class="text-sm">
                                {{ $insc->activite?->type?->label() ?? '—' }}
                            </td>
                            <td class="num grand">
                                {{ number_format((float) $insc->montant_du, 0, ',', ' ') }}
                            </td>
                            <td>
                                <x-badge class="{{ $insc->statut?->css() }}" point>{{ $insc->statut?->label() }}
                                </x-badge>
                            </td>
                            <td class="text-sm text-muted">
                                {{ optional($insc->date_inscription)->format('d/m/Y') }}
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @if ($insc->isActive())
                                            @can('activites.inscrire')
                                                <li>
                                                    <button class="dropdown-item text-danger btn-annuler-insc"
                                                            data-id="{{ $insc->id }}"
                                                            data-eleve="{{ $insc->eleve?->nom }} {{ $insc->eleve?->prenom }}"
                                                            data-activite="{{ $insc->activite?->libelle }}"
                                                            data-bs-toggle="modal" data-bs-target="#annulerInscriptionModal">
                                                        <i class="fas fa-times"></i> Annuler l'inscription
                                                    </button>
                                                </li>
                                            @endcan
                                        @else
                                            <li>
                                                <span class="dropdown-item text-muted small" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i> Inscription annulée
                                                </span>
                                            </li>
                                        @endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-user-check" :paragraphe="false"><p class="mb-0">Aucune inscription enregistrée.</p></x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$inscriptions->appends(request()->query())" /></div>

    {{-- Modale annulation --}}
    @can('activites.inscrire')
    <x-modale id="annulerInscriptionModal" aria-hidden="true" contenu-style="border:none;border-radius:16px;overflow:hidden;" entete-style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="annuler-inscription-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-exclamation-triangle me-2"></i>Annuler l'inscription</x-slot:titre>
        <p class="mb-2">
            Annuler l'inscription de <strong id="annuler-eleve">—</strong> à l'activité
            <strong id="annuler-activite">—</strong> ?
        </p>
        <p class="text-muted small mb-3">
            L'élève sera retiré de l'activité. Le montant dû disparaîtra de sa situation financière au prochain calcul du recouvrement.
        </p>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">
                <i class="fas fa-times me-1"></i> Confirmer
            </button>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    $('.btn-annuler-insc').on('click', function () {
        const id = $(this).data('id');
        $('#annuler-eleve').text($(this).data('eleve'));
        $('#annuler-activite').text($(this).data('activite'));
        $('#annuler-inscription-form').attr('action',
            `{{ url('services-eleves/activites-evenements/activites/inscriptions') }}/${id}/annuler`
        );
    });

    $('#annuler-inscription-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#annulerInscriptionModal').modal('hide');
                window.showToastThenReload(res.message || 'Inscription annulée.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });
});
</script>
@endpush