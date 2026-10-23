@extends('admin.layouts.app')

@section('title', 'Événements scolaires')
@section('page_icon', 'fa-calendar-check')
@section('page_title', 'Événements scolaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.activites-evenements.activites.index') }}">Activités</a></li>
    <li class="breadcrumb-item active">Événements</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.evenements.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.activites.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-futbol me-1"></i> Activités
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.inscriptions.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-user-check me-1"></i> Inscriptions
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.dashboard') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-chart-pie me-1"></i> Dashboard
    </x-bouton>
    @can('activites.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelEvenement" data-bs-toggle="modal" data-bs-target="#evenementModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvel événement
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    /* Jetons propres à cette page — les couleurs de marque restent
       celles de layouts/app.blade.php, non redéclarées ici. */
    .evt-page { --e-bg-soft:var(--eim-cream); --e-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    /* Cartes statistiques */
    .stat-card {
        background:#fff; border:1px solid var(--e-border); border-radius:12px;
        padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem;
        box-shadow:0 2px 8px rgba(142,18,22,.04);
        transition: transform .2s;
    }
    .stat-icon {
        width:46px; height:46px; border-radius:10px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-size:1.1rem;
    }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    /* Carte principale */
    .main-card {
        border:none; border-radius:16px;
        box-shadow: 0 10px 30px rgba(142,18,22,.06);
        background:#fff; overflow:hidden;
    }
    .main-card .card-header { border-bottom:1px solid var(--e-border) !important; }

    /* Filtres */
    .filtre-input {
        border-radius:10px; border:1px solid var(--e-border);
        padding:.55rem .9rem; font-size:.88rem;
    }
    .btn-filtrer {
        background: var(--school-red); color:#fff; border:none;
        border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem;
    }
    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600;
    }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite {
        border:1px solid var(--e-border); color:var(--school-ink); background:#fff; font-size:.82rem;
    }
    .btn-outline-scolarite:hover { background: var(--e-bg-soft); border-color: var(--school-red); color: var(--school-red); }
    .compteur-badge {
        display:inline-block; background: var(--e-bg-soft); color: var(--school-red);
        font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px;
        border:1px solid var(--e-border);
    }

    /* Tableau */
    .table-evenements thead tr { background: var(--e-bg-soft); }
    .table-evenements thead th {
        color: var(--school-red); font-size:.76rem; font-weight:700;
        text-transform:uppercase; padding:.9rem 1rem;
        border-bottom:2px solid var(--e-border); white-space:nowrap;
    }
    .table-evenements thead th i { color: var(--school-gold-dark); }
    .table-evenements tbody td {
        padding:.9rem 1rem; border-bottom:1px solid var(--e-border);
        font-size:.9rem; color: var(--school-ink); vertical-align:middle;
    }
    .table-evenements tbody tr:hover { background:var(--eim-hover); }

    .evt-nom { font-weight:700; color: var(--school-ink); display:block; }
    .evt-sub { font-size:.72rem; color: var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color: var(--school-red-dark); }

    /* Badges type événement */
    .badge-type {
        display:inline-flex; padding:.3em .8em; border-radius:999px;
        font-size:.72rem; font-weight:600;
    }
    .badge-type.excursion  { background:#e3f7ec; color:var(--eim-success); }
    .badge-type.voyage     { background:#e0ecfc; color:#2563eb; }
    .badge-type.sortie     { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-type.competition{ background:#fce4ec; color:#c2185b; }
    .badge-type.autre      { background:#eceff2; color:var(--eim-ink-soft); }

    /* Badges capacité */
    .badge-capacite {
        display:inline-flex; align-items:center; gap:5px; padding:.3em .8em;
        border-radius:999px; font-size:.72rem; font-weight:700;
    }
    .badge-capacite.ok   { background:#e3f7ec; color:var(--eim-success); }
    .badge-capacite.low  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-capacite.full { background:#fdecea; color:var(--school-red); }

    /* Barre de progression */
    .progress-bar-custom {
        height:6px; background:#eceff2; border-radius:3px; overflow:hidden; margin-top:4px;
    }
    .progress-bar-custom > div {
        height:100%; background:linear-gradient(90deg, var(--eim-success), #2ecc71);
        transition: width .3s ease;
    }
    .progress-bar-custom.warn > div { background:linear-gradient(90deg, var(--eim-gold-deep), var(--eim-gold)); }
    .progress-bar-custom.full > div { background:linear-gradient(90deg, var(--school-red-dark), var(--school-red)); }

    /* État vide */
    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    /* Modales */
    .modal-evt {
        border:none; border-radius:16px; overflow:hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,.15);
    }
    .modal-header-evt {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border-bottom:none; padding:1.15rem 1.5rem;
    }
    .form-section {
        background: var(--e-bg-soft); border:1px solid var(--e-border);
        border-radius:12px; padding:1.1rem;
    }
    .form-section-title {
        font-size:.76rem; font-weight:700; text-transform:uppercase;
        color: var(--school-red); margin-bottom:.75rem;
        display:flex; align-items:center; gap:6px;
    }
    .form-section .form-label { font-size:.82rem; font-weight:600; color: var(--school-ink); }
    .form-section .form-control, .form-section .form-select {
        border-radius:8px; border:1px solid var(--e-border);
        padding:.55rem .75rem; font-size:.88rem;
    }
    .btn-annuler {
        background:#fff; border:1px solid var(--e-border); color:var(--eim-ink-soft);
        border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem;
    }
    .btn-enregistrer {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border:none; border-radius:8px; font-weight:600;
        padding:.55rem 1.4rem; font-size:.88rem;
    }
    .btn-enregistrer:hover { color:#fff; }
</style>
@endsection

@section('contenu')
<div class="evt-page">

    {{-- Cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-calendar-check" couleur="blue" libelle="Événements total">{{ $stats['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-clock" couleur="green" libelle="À venir">{{ $stats['a_venir'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-users-cog" couleur="gold" libelle="À capacité limitée">{{ $stats['capacite_limitee'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3 text-end d-flex align-items-center justify-content-md-end">
            <x-compteur>{{ $evenements->total() }} événement(s)</x-compteur>
        </div>
    </div>

    {{-- Liste des événements à venir (encadré) --}}
    @if ($aVenir->isNotEmpty())
        <div class="alert alert-light border-0 small mb-3"
             style="background:#f0f6ff;color:#1e3a8a;border-left:3px solid #2563eb;">
            <i class="fas fa-calendar-alt me-1"></i>
            <strong>Prochains événements :</strong>
            @foreach ($aVenir as $evt)
                <x-compteur class="ms-1" style="background:#fff;border:1px solid #2563eb;color:#2563eb;">
                    {{ \Str::limit($evt->nom, 30) }} — {{ $evt->date_evenement->format('d/m/Y') }}
                </x-compteur>
            @endforeach
        </div>
    @endif

    {{-- Tableau principal --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Rechercher un événement..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="a_venir" value="1"
                               id="aVenir" @checked($filters['a_venir'] ?? false)>
                        <label class="form-check-label small" for="aVenir">À venir</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="capacite_limitee" value="1"
                               id="capLimitee" @checked($filters['capacite_limitee'] ?? false)>
                        <label class="form-check-label small" for="capLimitee">Capacité limitée</label>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </x-bouton>
                    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.evenements.index') }}" title="Réinitialiser">
                        <i class="fas fa-undo"></i>
                    </x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-evenements align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-calendar me-2"></i>Événement</th>
                            <th><i class="fas fa-tag me-2"></i>Type</th>
                            <th><i class="fas fa-clock me-2"></i>Date</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Participation</th>
                            <th class="text-center"><i class="fas fa-users me-2"></i>Capacité</th>
                            <th class="text-center"><i class="fas fa-percentage me-2"></i>Remplissage</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($evenements as $evt)
                        @php
                            $conf = $evt->nombre_confirmes;
                            $pre  = $evt->nombre_pre_inscrits;
                            $taux = $evt->taux_remplissage;
                            $barClass = $taux >= 90 ? 'full' : ($taux >= 60 ? 'warn' : '');
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('services-eleves.activites-evenements.evenements.show', $evt) }}"
                                   class="text-decoration-none">
                                    <span class="evt-nom">{{ $evt->nom }}</span>
                                    @if ($evt->isCapaciteLimitee())
                                        <span class="evt-sub">
                                            <i class="fas fa-exclamation-circle me-1"
                                               style="color:var(--school-gold-dark);"></i>
                                            Capacité limitée
                                            @if ($evt->date_limite_confirmation)
                                                · limite {{ $evt->date_limite_confirmation->format('d/m/Y') }}
                                            @endif
                                        </span>
                                    @endif
                                </a>
                            </td>
                            <td>
                                @if ($evt->type)
                                    <span class="badge-type {{ $evt->type->css() }}">
                                        {{ $evt->type->label() }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                {{ optional($evt->date_evenement)->format('d/m/Y') }}
                                @if ($evt->date_evenement && $evt->date_evenement->isPast())
                                    <div class="text-xs text-muted">Terminé</div>
                                @endif
                            </td>
                            <td class="num grand">
                                {{ number_format((float) $evt->participation, 0, ',', ' ') }}
                            </td>
                            <td class="text-center">
                                @if ($evt->isCapaciteLimitee())
                                    <span class="badge-capacite {{ $conf >= ($evt->capacite ?? 0) ? 'full' : ($conf / max(1, $evt->capacite) > 0.7 ? 'low' : 'ok') }}">
                                        <span class="badge-dot"></span>
                                        {{ $conf }} / {{ $evt->capacite }}
                                    </span>
                                    @if ($pre > 0)
                                        <div class="text-xs text-muted mt-1">{{ $pre }} pré-inscrit(s)</div>
                                    @endif
                                @else
                                    <span class="text-muted">Illimitée</span>
                                    <div class="text-xs text-muted mt-1">{{ $conf }} confirmé(s)</div>
                                @endif
                            </td>
                            <td class="text-center" style="min-width:120px;">
                                @if ($evt->isCapaciteLimitee())
                                    <span class="fw-bold">{{ number_format($taux, 0, ',', ' ') }} %</span>
                                    <div class="progress-bar-custom {{ $barClass }}">
                                        <div style="width:{{ min(100, max(0, $taux)) }}%"></div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('services-eleves.activites-evenements.evenements.show', $evt) }}">
                                                <i class="fas fa-eye"></i> Voir le détail
                                            </a>
                                        </li>
                                        @can('activites.inscrire')
                                            @if ($evt->isCapaciteLimitee() && ! $evt->isDateLimitePassee())
                                                <li>
                                                    <button type="button" class="dropdown-item btn-pre-inscrire"
                                                            data-id="{{ $evt->id }}"
                                                            data-nom="{{ $evt->nom }}"
                                                            data-bs-toggle="modal" data-bs-target="#preInscriptionModal">
                                                        <i class="fas fa-user-plus"></i> Pré-inscrire un élève
                                                    </button>
                                                </li>
                                            @endif
                                        @endcan
                                        @can('activites.gerer')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-evt"
                                                        data-id="{{ $evt->id }}"
                                                        data-nom="{{ $evt->nom }}"
                                                        data-type="{{ $evt->type?->value }}"
                                                        data-date="{{ $evt->date_evenement?->toDateString() }}"
                                                        data-participation="{{ $evt->participation }}"
                                                        data-capacite="{{ $evt->capacite }}"
                                                        data-capacite-limitee="{{ $evt->isCapaciteLimitee() ? 1 : 0 }}"
                                                        data-date-limite="{{ optional($evt->date_limite_confirmation)->toDateString() }}"
                                                        data-description="{{ $evt->description }}"
                                                        data-bs-toggle="modal" data-bs-target="#evenementModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-actions.supprimer :action="route('services-eleves.activites-evenements.evenements.destroy', $evt)" data-confirm-title="Supprimer cet événement ?" data-confirm-text="Les participations existantes empêcheront la suppression." />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-calendar-check" :paragraphe="false"><p class="mb-0">Aucun événement enregistré.</p>
                                    @can('activites.gerer')
                                        <p class="text-muted small mb-0">Cliquez sur « Nouvel événement » pour en créer un.</p>
                                    @endcan</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4">
        <x-pagination :liste="$evenements->appends(request()->query())" />
    </div>

    {{-- Modal création / édition --}}
    @can('activites.gerer')
    <x-modale id="evenementModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-evt" entete-class="modal-header-evt" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="evenement-form">@csrf
                    <input type="hidden" name="_method" id="evt-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="evt-title"><i class="fas fa-calendar-check me-2"></i>Nouvel événement</x-slot:titre>
        <x-section titre="Informations générales" icone="fas fa-info-circle">
            <div class="row g-3">
                <div class="col-md-8">
                    <x-libelle requis>Nom de l'événement</x-libelle>
                    <input type="text" name="nom" id="ev-nom" class="form-control"
                           maxlength="200" required>
                    <div class="text-danger text-xs mt-1" id="nom-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Type</x-libelle>
                    <select name="type" id="ev-type" class="form-select" required>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                    <div class="text-danger text-xs mt-1" id="type-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Date de l'événement</x-libelle>
                    <input type="date" name="date_evenement" id="ev-date"
                           class="form-control" value="{{ now()->addDays(7)->toDateString() }}" required>
                    <div class="text-danger text-xs mt-1" id="date_evenement-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Participation</x-libelle>
                    <input type="number" name="participation" id="ev-participation"
                           step="0.01" min="0" class="form-control" required>
                    <div class="text-danger text-xs mt-1" id="participation-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle>Capacité (optionnel)</x-libelle>
                    <input type="number" name="capacite" id="ev-capacite"
                           min="1" class="form-control">
                    <div class="form-text small">Nombre de places</div>
                </div>
            </div>
        </x-section>

        <x-section class="mt-3">
            <div class="form-section-title">
                <i class="fas fa-lock"></i> Capacité limitée &amp; pré-inscription
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="capacite_limitee" value="1" id="ev-cap-limitee">
                        <label class="form-check-label small" for="ev-cap-limitee">
                            <strong>Capacité limitée</strong> — les élèves doivent être pré-inscrits
                            avant tout paiement. Le paiement confirmera automatiquement leur place.
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <x-libelle>Date limite de confirmation</x-libelle>
                    <input type="date" name="date_limite_confirmation"
                           id="ev-date-limite" class="form-control">
                    <div class="form-text small">Obligatoire si capacité limitée</div>
                    <div class="text-danger text-xs mt-1" id="date_limite_confirmation-error"></div>
                </div>
            </div>
        </x-section>

        <x-section titre="Description" icone="fas fa-align-left" class="mt-3">
            <textarea name="description" id="ev-description" rows="3"
                      class="form-control" maxlength="2000"></textarea>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">
                <i class="fas fa-save me-1"></i> Enregistrer
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modal pré-inscription --}}
    @can('activites.inscrire')
    <x-modale id="preInscriptionModal" data-bs-backdrop="static" contenu-class="modal-evt" entete-class="modal-header-evt" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="pre-inscription-form">@csrf
                    <input type="hidden" name="evenement_id" id="pi-evt-id"></x-slot:formulaire>
        <x-slot:titre><i class="fas fa-user-plus me-2"></i>Pré-inscrire un élève</x-slot:titre>
        <div class="alert alert-light border-0 small mb-3"
             style="background:#fdf4e0;color:#7a5a00;">
            <i class="fas fa-info-circle me-1"></i>
            Cette action crée une <strong>pré-inscription</strong>.
            Le statut passera automatiquement à « Confirmé » à l'encaissement
            du paiement correspondant.
        </div>
        <p class="mb-3">
            Événement : <strong id="pi-evt-nom">—</strong>
        </p>
        <x-section>
            <x-libelle requis>
                Élève</x-libelle>
            @include('admin.partials.select-eleve')
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">
                <i class="fas fa-save me-1"></i> Pré-inscrire
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    // ================================================================
    // FORMULAIRE ÉVÉNEMENT (création + édition)
    // ================================================================
    const $form = $('#evenement-form');
    if ($form.length) {
        let currentId = null;

        // Bouton nouveau
        $('#btnNouvelEvenement').on('click', function () {
            currentId = null;
            $form[0].reset();
            $('#evt-method').val('POST');
            $('#evt-title').html('<i class="fas fa-calendar-check me-2"></i>Nouvel événement');
            $('#ev-date').val('{{ now()->addDays(7)->toDateString() }}');
            clearErrors();
        });

        // Bouton édition
        $(document).on('click', '.btn-edit-evt', function () {
            currentId = $(this).data('id');
            $('#ev-nom').val($(this).data('nom'));
            $('#ev-type').val($(this).data('type'));
            $('#ev-date').val($(this).data('date'));
            $('#ev-participation').val($(this).data('participation'));
            $('#ev-capacite').val($(this).data('capacite'));
            $('#ev-cap-limitee').prop('checked', $(this).data('capacite-limitee') == 1);
            $('#ev-date-limite').val($(this).data('date-limite'));
            $('#ev-description').val($(this).data('description'));
            $('#evt-method').val('PUT');
            $('#evt-title').html('<i class="fas fa-pen me-2"></i>Modifier l\'événement');
            clearErrors();
        });

        // Soumission
        $form.on('submit', function (e) {
            e.preventDefault();
            clearErrors();

            const url = currentId
                ? `{{ url('services-eleves/activites-evenements/evenements') }}/${currentId}`
                : `{{ route('services-eleves.activites-evenements.evenements.store') }}`;

            $.ajax({
                url, method: 'POST', data: $form.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#evenementModal').modal('hide');
                    window.showToastThenReload(res.message || 'Enregistré.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(f => {
                            $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]);
                        });
                    } else {
                        window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                    }
                }
            });
        });

        function clearErrors() { $form.find('[id$="-error"]').text(''); }
    }

    // ================================================================
    // FORMULAIRE PRÉ-INSCRIPTION
    // ================================================================
    $('.btn-pre-inscrire').on('click', function () {
        $('#pi-evt-id').val($(this).data('id'));
        $('#pi-evt-nom').text($(this).data('nom'));
    });

    $('#pre-inscription-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.activites-evenements.evenements.preInscrire") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#preInscriptionModal').modal('hide');
                window.showToastThenReload(res.message || 'Élève pré-inscrit.', 'success');
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