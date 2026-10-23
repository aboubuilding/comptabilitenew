@extends('admin.layouts.app')

@section('title', 'Abonnements bus')
@section('page_icon', 'fa-id-card')
@section('page_title', 'Abonnements bus')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Abonnements</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.abonnements.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="contour" type="button" id="btn-convertir-souscriptions" class="btn-sm rounded-pill px-3"
                  title="Les souscriptions transport de l'année deviennent des abonnements bus">
            <i class="fas fa-sync-alt me-1"></i> Synchroniser avec les souscriptions
        </x-bouton>
    @endcan
    @can('transport.abonner')
        <x-bouton variante="nouveau" type="button" id="btnNouvelAbonnement" data-bs-toggle="modal" data-bs-target="#abonnementModal">
            <i class="fas fa-plus-circle me-1"></i> Souscrire un élève
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    /* Réutilise les classes voitures/index */
    .abon-page { --ab-bg-soft:var(--eim-cream); --ab-border:var(--eim-border); }

    .stat-card { background:#fff; border:1px solid var(--ab-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--ab-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--ab-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--ab-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--ab-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--ab-border); }

    .table-ab thead tr { background:var(--ab-bg-soft); }
    .table-ab thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--ab-border); white-space:nowrap; }
    .table-ab tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--ab-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-ab tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-sens { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-sens.aller         { background:#e3f7ec; color:var(--eim-success); }
    .badge-sens.retour        { background:#e0ecfc; color:#2563eb; }
    .badge-sens.aller-retour  { background:#fdf4e0; color:var(--school-gold-dark); }

    .badge-statut.abandonne { background:#eceff2; color:var(--eim-ink-soft); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-ab { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-ab { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--ab-bg-soft); border:1px solid var(--ab-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--ab-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--ab-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="abon-page">

    @if ($aConvertir > 0)
        @can('transport.gerer')
            <div class="alert alert-warning d-flex align-items-center gap-2 small" id="bandeau-a-convertir">
                <i class="fas fa-exchange-alt"></i>
                <span><strong>{{ $aConvertir }}</strong> souscription(s) transport de l'année ne sont pas encore des abonnements bus.
                « Synchroniser avec les souscriptions » les transforme (montant annuel conservé, zone à préciser ensuite).</span>
            </div>
        @endcan
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-id-card" couleur="green" libelle="Abonnements actifs">{{ $compteurs['total_actifs'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="gold" libelle="Revenus mensuels">{{ number_format($compteurs['revenus_mensuels'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-calendar-alt" couleur="blue" libelle="Total annuel attendu">{{ number_format($compteurs['revenus_annuels'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-map-marker-alt" couleur="blue" libelle="À compléter">
                {{ $compteurs['sans_zone'] }} sans zone
                <x-slot:complement>{{ $compteurs['sans_gps'] }} sans position GPS</x-slot:complement>
            </x-stat>
        </div>
    </div>

    {{-- Répartition par zone --}}
    @if (! empty($parZone))
        <x-carte class="mb-4">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-map-marked-alt me-1" style="color:var(--school-gold-dark);"></i> Abonnements actifs par zone</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($parZone as $zone => $nb)
                        <x-compteur>
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $zone }} : <strong>{{ $nb }}</strong>
                        </x-compteur>
                    @endforeach
                </div>
            </div>
        </x-carte>
    @endif

    {{-- Tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Nom, matricule..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="zone_id" class="form-select filtre-input">
                        <option value="">— Zone —</option>
                        @foreach ($zones as $z)
                            <option value="{{ $z->id }}" @selected(($filters['zone_id'] ?? '') == $z->id)>{{ $z->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-none d-xl-block">
                    <select name="voiture_id" class="form-select filtre-input">
                        <option value="">— Véhicule —</option>
                        @foreach ($voitures as $v)
                            <option value="{{ $v->id }}" @selected(($filters['voiture_id'] ?? '') == $v->id)>{{ $v->plaque }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="gps" class="form-select filtre-input" id="filtre-gps">
                        <option value="">— Position GPS —</option>
                        <option value="avec" @selected(($filters['gps'] ?? '') === 'avec')>Renseignée</option>
                        <option value="sans" @selected(($filters['gps'] ?? '') === 'sans')>À renseigner</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        <option value="1" @selected(($filters['statut'] ?? '') == 1)>Actif</option>
                        <option value="0" @selected(($filters['statut'] ?? '') === '0')>Abandonné</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('services-eleves.transport.abonnements.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-ab align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Élève</th>
                            <th>Zone / ligne</th>
                            <th>Sens</th>
                            <th class="text-end">Mensuel</th>
                            <th class="text-end">Total dû</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($abonnements as $a)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">
                                    {{ $a->inscription?->eleve?->nom }} {{ $a->inscription?->eleve?->prenom }}
                                </div>
                                <div class="text-xs text-muted">{{ $a->inscription?->eleve?->matricule }}@if ($a->inscription?->classe) · {{ $a->inscription->classe->libelle }}@endif</div>
                            </td>
                            <td class="text-sm">
                                @if ($a->zone)
                                    <div><i class="fas fa-map-marker-alt me-1 text-muted"></i>{{ $a->zone->libelle }}</div>
                                @else
                                    <div class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>Zone à préciser</div>
                                @endif
                                @if ($a->ligne || $a->voiture)
                                    <div class="text-xs text-muted">
                                        @if ($a->ligne)<i class="fas fa-route me-1"></i>{{ $a->ligne->libelle }}@endif
                                        @if ($a->voiture) · <i class="fas fa-bus me-1"></i>{{ $a->voiture->plaque }}@endif
                                    </div>
                                @endif
                                @if ($a->aUnePosition())
                                    <a class="text-xs" href="{{ $a->lienCarte() }}" target="_blank" rel="noopener"><i class="fas fa-location-arrow me-1"></i>Domicile sur la carte</a>
                                @endif
                            </td>
                            <td>
                                <span class="badge-sens {{ $a->sens?->css() }}">
                                    <span class="badge-dot"></span>{{ $a->sens?->label() }}
                                </span>
                            </td>
                            <td class="num">{{ number_format((float) $a->montant_mensuel, 0, ',', ' ') }}</td>
                            <td class="num grand">{{ number_format((float) $a->montant_total_du, 0, ',', ' ') }}</td>
                            <td>
                                <x-badge class="{{ $a->statut == 1 ? 'actif' : 'abandonne' }}" point>{{ $a->statut == 1 ? 'Actif' : 'Abandonné' }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('transport.gerer')
                                            <li>
                                                <button class="dropdown-item btn-assigner"
                                                        data-id="{{ $a->id }}"
                                                        data-zone="{{ $a->zone_id }}"
                                                        data-sens="{{ $a->sens?->value }}"
                                                        data-bs-toggle="modal" data-bs-target="#assignerModal">
                                                    <i class="fas fa-map-marker-alt"></i> Zone et trajet
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item btn-position"
                                                        data-id="{{ $a->id }}"
                                                        data-eleve="{{ $a->inscription?->eleve?->nom }} {{ $a->inscription?->eleve?->prenom }}"
                                                        data-lat="{{ $a->latitude }}"
                                                        data-lng="{{ $a->longitude }}"
                                                        data-bs-toggle="modal" data-bs-target="#positionModal">
                                                    <i class="fas fa-location-arrow"></i> Position GPS du domicile
                                                </button>
                                            </li>
                                        @endcan
                                        @if ($a->statut == 1)
                                            @can('transport.gerer')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger btn-abandonner"
                                                            data-id="{{ $a->id }}"
                                                            data-eleve="{{ $a->inscription?->eleve?->nom }} {{ $a->inscription?->eleve?->prenom }}"
                                                            data-bs-toggle="modal" data-bs-target="#abandonModal">
                                                        <i class="fas fa-times"></i> Abandonner
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-id-card">Aucun abonnement.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$abonnements->appends(request()->query())" /></div>

    {{-- Modale souscription --}}
    @can('transport.abonner')
    <x-modale id="abonnementModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-ab" entete-class="modal-header-ab" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="abonnement-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-id-card me-2"></i>Souscrire un élève au bus</x-slot:titre>
        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
            <i class="fas fa-info-circle me-1"></i>
            Cette action crée l'engagement <strong>indépendamment du paiement</strong>.
            Le montant dû apparaîtra immédiatement dans la situation financière de l'élève.
        </div>

        <x-section titre="Élève" icone="fas fa-user-graduate" class="mb-3">
            <div>
                <x-libelle requis>Élève</x-libelle>
                @include('admin.partials.select-eleve')
            </div>
        </x-section>

        <x-section titre="Zone et trajet" icone="fas fa-route" class="mb-3">
            <div class="row g-3">
                <div class="col-md-8">
                    <x-libelle>Zone</x-libelle>
                    <select name="zone_id" id="souscription-zone" class="form-select">
                        <option value="">— À préciser plus tard —</option>
                        @foreach ($zones as $z)
                            <option value="{{ $z->id }}" data-montant="{{ (float) $z->tarif_base ?: '' }}">
                                {{ $z->libelle }}@if ((float) $z->tarif_base) — {{ number_format((float) $z->tarif_base, 0, ',', ' ') }} / mois (indicatif)@endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text small">La ligne, le chauffeur et le véhicule sont ceux de la zone.</div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Sens</x-libelle>
                    <select name="sens" class="form-select" required>
                        @foreach ($sens as $v => $l)
                            <option value="{{ $v }}" @selected($v === 'aller_retour')>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-section>

        <x-section titre="Abonnement" icone="fas fa-money-bill-wave">
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle requis>Date de début</x-libelle>
                    <input type="date" name="date_debut" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Montant mensuel</x-libelle>
                    <input type="number" name="montant_mensuel" id="souscription-mensuel" step="1" min="1" class="form-control" required>
                    <div class="form-text small">Tarif de la zone proposé, modifiable.</div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Nombre de mois</x-libelle>
                    <input type="number" name="nombre_mois" min="1" max="12" value="9" class="form-control" required>
                </div>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Souscrire</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modale assignation --}}
    @can('transport.gerer')
    <x-modale id="assignerModal" data-bs-backdrop="static" contenu-class="modal-ab" entete-class="modal-header-ab" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="assigner-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-map-marker-alt me-2"></i>Zone et trajet</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle>Zone</x-libelle>
                <select name="zone_id" id="assign-zone" class="form-select">
                    <option value="">— Aucune —</option>
                    @foreach ($zones as $z)
                        <option value="{{ $z->id }}">{{ $z->libelle }}@if ($z->ligne) — {{ $z->ligne->libelle }}@endif</option>
                    @endforeach
                </select>
                <div class="form-text small">La ligne et le véhicule suivent la zone. Le montant de l'abonnement ne change pas.</div>
            </div>
            <div class="mb-0">
                <x-libelle>Sens</x-libelle>
                <select name="sens" id="assign-sens" class="form-select">
                    @foreach ($sens as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modale abandon --}}
    @can('transport.gerer')
    <x-modale id="abandonModal" aria-hidden="true" contenu-class="modal-ab" entete-style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="abandon-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-exclamation-triangle me-2"></i>Abandonner l'abonnement</x-slot:titre>
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

    {{-- Modale position GPS du domicile (renseignée après la souscription) --}}
    <x-modale id="positionModal" data-bs-backdrop="static" contenu-class="modal-ab" entete-class="modal-header-ab" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="position-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-location-arrow me-2"></i>Position GPS du domicile</x-slot:titre>
        <p class="small mb-3">Élève : <strong id="position-eleve">—</strong></p>
        <x-section>
            <div class="mb-3">
                <x-libelle for="position-coller">Coordonnées (copiées depuis une carte)</x-libelle>
                <input type="text" id="position-coller" class="form-control" placeholder="Ex : 6.1725, 1.2314">
                <div class="form-text small">Collez « latitude, longitude » : les deux champs se remplissent.</div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle for="position-lat">Latitude</x-libelle>
                    <input type="number" name="latitude" id="position-lat" class="form-control" step="0.0000001" min="-90" max="90">
                </div>
                <div class="col-md-6">
                    <x-libelle for="position-lng">Longitude</x-libelle>
                    <input type="number" name="longitude" id="position-lng" class="form-control" step="0.0000001" min="-180" max="180">
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <x-bouton variante="contour" type="button" id="position-ici" class="btn-sm"><i class="fas fa-crosshairs me-1"></i>Ma position actuelle</x-bouton>
                <a href="#" id="position-carte" class="btn btn-sm btn-outline-secondary d-none" target="_blank" rel="noopener"><i class="fas fa-map me-1"></i>Voir sur la carte</a>
                <x-bouton variante="contour" type="button" id="position-effacer" class="btn-sm"><i class="fas fa-eraser me-1"></i>Effacer</x-bouton>
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
    // Souscription
    $('#abonnement-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.transport.abonnements.store") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#abonnementModal').modal('hide');
                window.showToastThenReload(res.message || 'Abonnement créé.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    // La zone propose son tarif (indicatif, modifiable)
    $('#souscription-zone').on('change', function () {
        const tarif = $(this).find(':selected').data('montant');
        if (tarif) $('#souscription-mensuel').val(tarif);
    });

    // Synchronisation : souscriptions transport -> abonnements bus
    $('#btn-convertir-souscriptions').on('click', function () {
        if (!confirm("Transformer les souscriptions transport de l'année en abonnements bus ?\n\nLe montant annuel est conservé ; la zone sera à préciser ensuite. Rien n'est supprimé.")) return;
        const $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ route("services-eleves.transport.abonnements.convertir-souscriptions") }}',
            method: 'POST', data: { _token: '{{ csrf_token() }}' },
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                const b = res.bilan || res.data?.bilan || {};
                window.showToastThenReload(`${b.convertis ?? 0} abonnement(s) créé(s), ${b.paiements ?? 0} ligne(s) de paiement rattachée(s)`
                    + (b.ignores ? `, ${b.ignores} ignorée(s) (montant nul)` : '') + '.', 'success');
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });

    // Zone et trajet
    $('.btn-assigner').on('click', function () {
        $('#assign-zone').val($(this).data('zone') || '');
        $('#assign-sens').val($(this).data('sens') || 'aller_retour');
        $('#assigner-form').data('id', $(this).data('id'));
    });

    // Position GPS du domicile
    const majLienCarte = function () {
        const lat = $('#position-lat').val(), lng = $('#position-lng').val();
        $('#position-carte').toggleClass('d-none', !(lat && lng)).attr('href', `https://www.google.com/maps?q=${lat},${lng}`);
    };
    $('.btn-position').on('click', function () {
        $('#position-form').data('id', $(this).data('id'));
        $('#position-eleve').text($(this).data('eleve'));
        $('#position-lat').val($(this).data('lat') ?? '');
        $('#position-lng').val($(this).data('lng') ?? '');
        $('#position-coller').val('');
        majLienCarte();
    });
    $('#position-coller').on('input', function () {
        const m = $(this).val().match(/(-?\d+(?:[.,]\d+)?)\s*[,;\s]\s*(-?\d+(?:[.,]\d+)?)/);
        if (m) {
            $('#position-lat').val(m[1].replace(',', '.'));
            $('#position-lng').val(m[2].replace(',', '.'));
            majLienCarte();
        }
    });
    $('#position-lat, #position-lng').on('input', majLienCarte);
    $('#position-effacer').on('click', function () {
        $('#position-lat, #position-lng, #position-coller').val('');
        majLienCarte();
    });
    $('#position-ici').on('click', function () {
        if (!navigator.geolocation) return window.showToast("La géolocalisation n'est pas disponible sur cet appareil.", 'error');
        navigator.geolocation.getCurrentPosition(function (p) {
            $('#position-lat').val(p.coords.latitude.toFixed(7));
            $('#position-lng').val(p.coords.longitude.toFixed(7));
            majLienCarte();
        }, function () { window.showToast("Position introuvable (autorisation refusée ?).", 'error'); });
    });
    $('#position-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: `{{ url('services-eleves/transport/abonnements') }}/${$(this).data('id')}/position`,
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#positionModal').modal('hide');
                window.showToastThenReload(res.message || 'Position enregistrée.', 'success');
            },
            error: function (xhr) {
                const erreurs = xhr.responseJSON?.errors;
                window.showToast(erreurs ? Object.values(erreurs)[0][0] : (xhr.responseJSON?.message || 'Erreur.'), 'error');
            }
        });
    });

    $('#assigner-form').on('submit', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({
            url: `{{ url('services-eleves/transport/abonnements') }}/${id}/assigner`,
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#assignerModal').modal('hide');
                window.showToastThenReload(res.message || 'Assignation mise à jour.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });

    // Abandon
    $('.btn-abandonner').on('click', function () {
        const id = $(this).data('id');
        $('#abandon-eleve').text($(this).data('eleve'));
        $('#abandon-form').attr('action', `{{ url('services-eleves/transport/abonnements') }}/${id}/abandonner`);
    });

    $('#abandon-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#abandonModal').modal('hide');
                window.showToastThenReload(res.message || 'Abonnement abandonné.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });
});
</script>
@endpush