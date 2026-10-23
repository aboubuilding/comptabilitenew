@extends('admin.layouts.app')

@section('title', 'Souscriptions')
@section('page_icon', 'fa-handshake')
@section('page_title', 'Souscriptions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.frais-ecoles.index') }}">Frais scolaires</a></li>
    <li class="breadcrumb-item active">Souscriptions</li>
@endsection

@section('page_actions')
    @php $requete = ['onglet' => $onglet] + request()->except(['page', 'onglet']); @endphp
    <div class="dropdown">
        <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3 dropdown-toggle" id="btn-exporter" data-bs-toggle="dropdown">
            <i class="fas fa-file-export me-1"></i> Exporter
        </x-bouton>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" id="export-excel" href="{{ route('finances.souscriptions.export', $requete) }}"><i class="fas fa-file-excel me-2 text-success"></i>Excel</a></li>
            <li><a class="dropdown-item" id="export-pdf" href="{{ route('finances.souscriptions.exportPdf', $requete) }}"><i class="fas fa-file-pdf me-2 text-danger"></i>PDF</a></li>
        </ul>
    </div>
    @can('souscriptions.gerer')
        <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3" id="btn-synchroniser">
            <i class="fas fa-sync-alt me-1"></i> Synchroniser les souscriptions
        </x-bouton>
        <x-bouton variante="nouveau" type="button" data-bs-toggle="modal" data-bs-target="#souscModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle souscription
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .text-xs { font-size:.72rem; }
    .table-sousc thead tr { background:var(--eim-cream); }
    .table-sousc thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--eim-border); white-space:nowrap; }
    .table-sousc tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--eim-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-sousc tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    .remise-badge { display:inline-flex; align-items:center; gap:4px; padding:.2em .6em; border-radius:999px; background:#fdf4e0; color:var(--school-gold-dark); font-size:.7rem; font-weight:700; }
    .nature { display:inline-flex; align-items:center; gap:5px; background:var(--eim-cream); color:var(--school-red); font-weight:600; font-size:.7rem; padding:.18rem .6rem; border-radius:999px; border:1px solid var(--eim-border); }
    .reference { font-family:monospace; font-size:.8rem; background:var(--eim-cream); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }

    /* Tableau de bord par service */
    .service-card { background:#fff; border:1px solid var(--eim-border); border-radius:14px; padding:1rem 1.1rem; height:100%; box-shadow:0 2px 8px rgba(142,18,22,.04); }
    .service-card .entete { display:flex; align-items:center; gap:.75rem; margin-bottom:.75rem; }
    .service-card .nb { font-size:1.5rem; font-weight:800; color:var(--school-ink); line-height:1; }
    .service-card .titre { font-size:.76rem; color:var(--school-muted); font-weight:600; }
    .service-card dl { display:grid; grid-template-columns:auto 1fr; gap:.2rem .75rem; margin:0; font-size:.82rem; }
    .service-card dt { color:var(--school-muted); font-weight:500; }
    .service-card dd { margin:0; text-align:right; font-weight:700; font-variant-numeric:tabular-nums; }
    .service-card dd.reste { color:var(--school-red); }
    .jauge { height:6px; border-radius:999px; background:#f1ebdf; overflow:hidden; margin-top:.7rem; }
    .jauge > span { display:block; height:100%; background:var(--eim-success); border-radius:999px; }

    .resume-montant { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.5rem .75rem; display:flex; flex-direction:column; }
    .resume-montant span { font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .onglets { border-bottom:2px solid var(--eim-border); gap:.25rem; flex-wrap:nowrap; overflow-x:auto; }
    .onglets .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.9rem; padding:.75rem 1rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .onglets .compte { display:inline-block; min-width:22px; padding:0 .45rem; border-radius:999px; background:var(--eim-cream); color:var(--school-red); font-size:.72rem; margin-left:.35rem; border:1px solid var(--eim-border); }
    .indicateurs { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:.6rem; }
    .indicateur { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.55rem .8rem; }
    .indicateur span { display:block; font-size:.66rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .indicateur strong { font-size:1rem; font-variant-numeric:tabular-nums; }
    .pastille-scolarite { display:inline-block; font-size:.66rem; font-weight:700; padding:.08rem .5rem; border-radius:999px; white-space:nowrap; }
    .pastille-scolarite.niveau { background:#e0ecfc; color:#2563eb; }
    .pastille-scolarite.classe { background:var(--eim-cream); color:var(--school-red); border:1px solid var(--eim-border); }
    .table-niveaux th { font-size:.7rem; text-transform:uppercase; color:var(--school-red); background:var(--eim-cream); }
    .table-niveaux td, .table-niveaux th { padding:.4rem .7rem; font-size:.82rem; }
    .modal-sousc .modal-header { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; }
    .modal-sousc .modal-title { color:#fff; font-size:1rem; font-weight:700; }
</style>
@endsection

@section('contenu')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $icones = [
        \App\Domain\Finances\Types\TypeDetail::CANTINE->value   => ['fas fa-utensils', 'gold'],
        \App\Domain\Finances\Types\TypeDetail::BUS->value       => ['fas fa-bus-alt', 'blue'],
        \App\Domain\Finances\Types\TypeDetail::EVENEMENT->value => ['fas fa-calendar-check', 'red'],
        \App\Domain\Finances\Types\TypeDetail::ACTIVITE->value  => ['fas fa-futbol', 'green'],
    ];
    $etats = [
        'solde'   => ['Soldé', 'encaisse'],
        'partiel' => ['En cours', 'attente'],
        'impaye'  => ['Non payé', 'retard'],
    ];
@endphp
<div class="container-fluid p-0">

    <div class="d-flex align-items-center gap-2 mb-3" id="annee-souscriptions">
        <span class="nature" style="font-size:.8rem; padding:.35rem .8rem;"><i class="fas fa-calendar-alt"></i> Année scolaire : {{ $annee?->libelle }}</span>
    </div>

    {{-- ==================== Tableau de bord par service (Direction et Administrateur) ==================== --}}
    @if ($tableauDeBord)
        <div class="row g-3 mb-4" id="tableau-de-bord">
            @foreach ($tableauDeBord as $service)
                @php
                    [$icone, $couleur] = $icones[$service['type']->value];
                    $taux = $service['attendu'] > 0 ? min(100, round($service['percu'] / $service['attendu'] * 100)) : 0;
                @endphp
                <div class="col-sm-6 col-xl-3">
                    <div class="service-card" data-service="{{ $service['type']->css() }}">
                        <div class="entete">
                            <div class="stat-icon {{ $couleur }}"><i class="{{ $icone }}"></i></div>
                            <div>
                                <div class="nb">{{ $fmt($service['nb']) }}</div>
                                <div class="titre">souscription(s) {{ mb_strtolower($service['type']->label()) }}</div>
                            </div>
                        </div>
                        <dl>
                            <dt>Montant attendu</dt><dd class="attendu">{{ $fmt($service['attendu']) }} F</dd>
                            <dt>Encaissé</dt><dd class="percu">{{ $fmt($service['percu']) }} F</dd>
                            <dt>Reste à percevoir</dt><dd class="reste">{{ $fmt($service['reste']) }} F</dd>
                        </dl>
                        <div class="jauge" title="{{ $taux }} % encaissé"><span style="width:{{ $taux }}%;"></span></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-carte>
        {{-- ==================== Onglets ==================== --}}
        <ul class="nav onglets px-3 pt-2" id="onglets-souscriptions">
            @foreach ($onglets as $cle => $libelle)
                <li class="nav-item">
                    <a class="nav-link @if ($onglet === $cle) active @endif" id="onglet-{{ $cle }}"
                       href="{{ route('finances.souscriptions.index', ['onglet' => $cle]) }}">
                        <i class="fas {{ $cle === 'non-a-jour' ? 'fa-exclamation-circle' : 'fa-list' }} me-1"></i>{{ $libelle }}
                        <span class="compte">{{ $cle === 'non-a-jour' ? $compteNonAJour : $compteToutes }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- ==================== Indicateurs de l'onglet, filtres compris ==================== --}}
        @if ($statistiques)
            <div class="px-4 pt-3" id="indicateurs">
                <div class="indicateurs">
                    <div class="indicateur"><span>Élèves concernés</span><strong id="stat-eleves">{{ $fmt($statistiques['eleves']) }}</strong></div>
                    @if ($onglet === 'toutes')
                    <div class="indicateur"><span>Souscriptions réglées</span><strong id="stat-soldees" class="text-success">{{ $fmt($statistiques['soldees']) }}</strong></div>
                    @endif
                    <div class="indicateur"><span>Souscriptions partielles</span><strong id="stat-partielles">{{ $fmt($statistiques['partielles']) }}</strong></div>
                    <div class="indicateur"><span>Souscriptions impayées</span><strong id="stat-impayees" class="text-danger">{{ $fmt($statistiques['impayees']) }}</strong></div>
                    <div class="indicateur"><span>Montant attendu</span><strong id="stat-attendu">{{ $fmt($statistiques['attendu']) }} F</strong></div>
                    <div class="indicateur"><span>Encaissé</span><strong id="stat-percu" class="text-success">{{ $fmt($statistiques['percu']) }} F</strong></div>
                    <div class="indicateur"><span>Reste à recouvrer</span><strong id="stat-reste" class="text-danger">{{ $fmt($statistiques['reste']) }} F</strong></div>
                </div>
                <details class="mt-2" id="stats-niveaux">
                    <summary class="text-xs fw-semibold" style="cursor:pointer; color:var(--school-red);">Répartition par niveau ({{ $statistiques['niveaux']->count() }})</summary>
                    <p class="text-xs text-muted mt-2 mb-1" id="aide-niveaux">
                        Un élève peut avoir plusieurs souscriptions (cantine, transport, activité…) : la colonne « Élèves » compte les élèves,
                        les colonnes suivantes comptent leurs souscriptions — leur total peut donc dépasser le nombre d'élèves.
                        @if ($onglet === 'non-a-jour') Cet onglet ne retient que les souscriptions qui ont encore un reste à payer. @endif
                    </p>
                    <div class="table-responsive mt-2">
                        <table class="table table-niveaux align-middle mb-0">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="align-bottom">Niveau</th>
                                    <th rowspan="2" class="text-end align-bottom">Élèves</th>
                                    <th colspan="{{ $onglet === 'toutes' ? 4 : 3 }}" class="text-center">Souscriptions de ces élèves</th>
                                    <th rowspan="2" class="text-end align-bottom">Montant attendu</th>
                                    <th rowspan="2" class="text-end align-bottom">Encaissé</th>
                                    <th rowspan="2" class="text-end align-bottom">Reste</th>
                                </tr>
                                <tr>
                                    <th class="text-end">Total</th>
                                    @if ($onglet === 'toutes')
                                        <th class="text-end">Réglées</th>
                                    @endif
                                    <th class="text-end">Payées en partie</th>
                                    <th class="text-end">Sans aucun paiement</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($statistiques['niveaux'] as $n)
                                <tr>
                                    <td>{{ $n->niveau ?? 'Sans niveau' }}</td>
                                    <td class="text-end fw-bold">{{ $n->eleves }}</td>
                                    <td class="text-end">{{ $n->souscriptions }}</td>
                                    @if ($onglet === 'toutes')
                                        <td class="text-end text-success">{{ $n->soldees }}</td>
                                    @endif
                                    <td class="text-end">{{ $n->partielles }}</td>
                                    <td class="text-end text-danger">{{ $n->impayees }}</td>
                                    <td class="num">{{ $fmt($n->attendu) }}</td>
                                    <td class="num">{{ $fmt($n->percu) }}</td>
                                    <td class="num text-danger">{{ $fmt($n->reste) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        @endif

        {{-- ==================== Filtres (l'année est celle de l'en-tête) ==================== --}}
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center" id="filtres-souscriptions">
                <input type="hidden" name="onglet" value="{{ $onglet }}">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Nom, prénom, matricule..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="niveau_id" class="form-select filtre-input" title="Niveau">
                        <option value="">— Niveau —</option>
                        @foreach ($niveaux as $n)
                            <option value="{{ $n->id }}" @selected(($filters['niveau_id'] ?? '') == $n->id)>{{ $n->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="classe_id" class="form-select filtre-input" title="Classe">
                        <option value="">— Classe —</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" @selected(($filters['classe_id'] ?? '') == $c->id)>{{ $c->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select filtre-input" title="Type de souscription">
                        <option value="">— Type —</option>
                        @foreach ($types as $t)
                            <option value="{{ $t->value }}" @selected(($filters['type'] ?? '') == $t->value)>{{ $t->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="paiement" class="form-select filtre-input" title="Statut de paiement">
                        <option value="">— Statut —</option>
                        @foreach ($etats as $cle => [$libelle])
                            @continue($onglet === 'non-a-jour' && $cle === 'solde')
                            <option value="{{ $cle }}" @selected(($filters['paiement'] ?? '') === $cle)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($filters['frais_ecole_id'] ?? null)
                    <input type="hidden" name="frais_ecole_id" value="{{ $filters['frais_ecole_id'] }}">
                @endif
                <div class="col-md-1 d-flex gap-1">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.souscriptions.index', ['onglet' => $onglet]) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-sousc align-middle mb-0" id="table-souscriptions">
                <thead>
                    <tr>
                        <th class="ps-4">Élève</th>
                        <th>Souscription</th>
                        <th class="text-end">Montant</th>
                        <th>Règlement</th>
                        <th class="text-center pe-4" style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($souscriptions as $s)
                    @php
                        $eleve = $s->inscription?->eleve;
                        [$etatLibelle, $etatClasse] = $etats[$s->etat_paiement];
                    @endphp
                    <tr data-souscription="{{ $s->id }}">
                        <td class="ps-4">
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <span class="fw-semibold me-1">{{ $eleve?->nom }} {{ $eleve?->prenom }}</span>
                                @if ($s->inscription?->niveau)
                                    <span class="pastille-scolarite niveau" title="Niveau">{{ $s->inscription->niveau->libelle }}</span>
                                @endif
                                @if ($s->inscription?->classe)
                                    <span class="pastille-scolarite classe" title="Classe">{{ $s->inscription->classe->libelle }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-muted">{{ $eleve?->matricule }}</div>
                        </td>
                        <td>
                            <span class="nature"><i class="{{ $icones[$s->type_paiement?->value][0] ?? 'fas fa-tag' }}"></i>{{ $s->type_paiement?->label() ?? '—' }}</span>
                            <div class="text-sm mt-1">{{ $s->intitule }}</div>
                            <div class="text-xs text-muted"><i class="far fa-calendar me-1"></i>{{ optional($s->date_souscription)->format('d/m/Y') ?? '—' }}</div>
                        </td>
                        <td class="text-end">
                            <div class="num" style="color:var(--school-red-dark);">{{ number_format($s->montant_net, 0, ',', ' ') }} F</div>
                            @if ((float) $s->taux_remise > 0)
                                <div class="text-xs text-muted">{{ number_format((float) $s->montant_annuel_prevu, 0, ',', ' ') }} F
                                    <span class="remise-badge"><i class="fas fa-tag"></i>− {{ number_format((float) $s->taux_remise, 1, ',', ' ') }} %</span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <x-badge class="{{ $etatClasse }}" point>{{ $etatLibelle }}</x-badge>
                            <div class="text-xs text-muted mt-1">
                                Encaissé {{ $fmt($s->montant_paye) }} F
                                @if ($s->reste > 0) · reste <strong class="text-danger">{{ $fmt($s->reste) }} F</strong>@endif
                            </div>
                        </td>
                        <td class="text-center pe-4">
                            <x-actions>
                                <li>
                                    <button type="button" class="dropdown-item btn-paiements" data-id="{{ $s->id }}">
                                        <i class="fas fa-receipt"></i> Voir les paiements
                                    </button>
                                </li>
                                @can('souscriptions.remise')
                                <li>
                                    <button class="dropdown-item btn-edit-remise"
                                            data-id="{{ $s->id }}"
                                            data-eleve="{{ $eleve?->nom }} {{ $eleve?->prenom }}"
                                            data-taux="{{ $s->taux_remise }}"
                                            data-bs-toggle="modal" data-bs-target="#remiseModal">
                                        <i class="fas fa-tag"></i> Modifier la remise
                                    </button>
                                </li>
                                @endcan
                                @can('souscriptions.gerer')
                                <li><hr class="dropdown-divider"></li>
                                <x-actions.supprimer :action="route('finances.souscriptions.destroy', $s)" />
                                @endcan
                            </x-actions>
                        </td>
                    </tr>
                @empty
                    <x-vide colspan="5" icone="fas fa-handshake">Aucune souscription.</x-vide>
                @endforelse
                </tbody>
            </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$souscriptions->appends(request()->query())" /></div>

    {{-- ==================== Bilan de la synchronisation ==================== --}}
    @can('souscriptions.gerer')
    <x-modale id="syncModal" class="modal-sousc" corps-id="sync-contenu" croix-blanche>
        <x-slot:titre><i class="fas fa-sync-alt me-2"></i>Synchronisation des souscriptions</x-slot:titre>
        <x-slot:pied>
            <x-bouton variante="annuler" type="button" id="sync-fermer">Fermer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- ==================== Paiements liés à une souscription ==================== --}}
    <x-modale id="paiementsModal" class="modal-sousc" dialogue="modal-lg modal-dialog-centered" corps-id="paiements-contenu" croix-blanche>
        <x-slot:titre><i class="fas fa-receipt me-2"></i>Paiements de la souscription</x-slot:titre>
        <x-slot:pied>
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
        </x-slot:pied>
    </x-modale>

    {{-- ==================== Nouvelle souscription ==================== --}}
    @can('souscriptions.gerer')
    <x-modale id="souscModal" data-bs-backdrop="static" contenu-class="modal-frais" entete-class="modal-header-frais" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="sousc-form" action="{{ route('finances.souscriptions.store') }}" method="POST">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-handshake me-2"></i>Nouvelle souscription</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Frais</x-libelle>
                <select name="frais_ecole_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($frais as $f)
                        <option value="{{ $f->id }}">{{ $f->libelle }} — {{ number_format((float) $f->montant, 2, ',', ' ') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <x-libelle requis>Élève (inscription)</x-libelle>
                <select name="inscription_id" id="sousc-inscription" class="form-select" required style="width:100%;">
                    <option value=""></option>
                    @foreach ($inscriptions as $inscription)
                        <option value="{{ $inscription->id }}">
                            {{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}
                            @if ($inscription->eleve->matricule) ({{ $inscription->eleve->matricule }}) @endif
                            — {{ $inscription->classe?->libelle ?? $inscription->niveau?->libelle ?? 'sans classe' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text small">Élèves inscrits sur l'année scolaire active.</div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle>Taux de remise (%)</x-libelle>
                    <input type="number" name="taux_remise" step="0.01" min="0" max="100" value="0" class="form-control">
                </div>
                <div class="col-md-6">
                    <x-libelle>Date de souscription</x-libelle>
                    <input type="date" name="date_souscription" class="form-control" value="{{ now()->toDateString() }}">
                </div>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- ==================== Remise ==================== --}}
    @can('souscriptions.remise')
    <x-modale id="remiseModal" data-bs-backdrop="static" contenu-class="modal-frais" entete-class="modal-header-frais" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="remise-form" method="POST" action="">@csrf @method('PUT')</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-tag me-2"></i>Modifier la remise</x-slot:titre>
        <p>Élève : <strong id="remise-eleve">—</strong></p>
        <div class="mb-0">
            <x-libelle requis>Taux de remise (%)</x-libelle>
            <input type="number" name="taux_remise" id="remise-taux" step="0.01" min="0" max="100" class="form-control" required>
        </div>
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
    const URL_SOUSCRIPTIONS = '{{ url('finances/souscriptions') }}';
    const erreur = xhr => xhr.responseJSON?.message || 'Une erreur est survenue.';

    $('#sousc-inscription').select2({
        dropdownParent: $('#souscModal'),
        placeholder: 'Rechercher un élève...',
        width: '100%',
    });

    // Synchronisation des souscriptions de l'année de l'en-tête (confirmation, puis bilan)
    $('#btn-synchroniser').on('click', function () {
        const $btn = $(this);
        Swal.fire({
            title: 'Synchroniser les souscriptions ?',
            text: "Les souscriptions manquantes de l'année {{ $annee?->libelle }} seront créées à partir des inscriptions et des paiements. Rien n'est supprimé.",
            icon: 'question', showCancelButton: true, confirmButtonColor: '#E31E24',
            confirmButtonText: 'Oui, synchroniser', cancelButtonText: 'Annuler',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;
            $btn.prop('disabled', true);
            $.ajax({
                url: '{{ route('finances.souscriptions.synchroniser') }}', method: 'POST',
                data: { _token: '{{ csrf_token() }}' }, headers: { 'Accept': 'application/json' },
            }).done(function (res) {
                const b = res.bilan || res.data?.bilan || {};
                const lignes = Object.entries(b.services || {}).map(([nom, nb]) => '<li>' + $('<i>').text(nom).html() + ' : <strong>' + nb + '</strong> créée(s)</li>').join('');
                const erreurs = (b.erreurs || []).map(e => '<li>' + $('<i>').text(e).html() + '</li>').join('');
                $('#sync-contenu').html(
                    '<div class="indicateurs mb-3">'
                    + '<div class="indicateur"><span>Créées</span><strong id="sync-creees">' + (b.creees ?? 0) + '</strong></div>'
                    + '<div class="indicateur"><span>Mises à jour</span><strong id="sync-maj">' + (b.mises_a_jour ?? 0) + '</strong></div>'
                    + '<div class="indicateur"><span>Ignorées (déjà à jour)</span><strong id="sync-ignorees">' + (b.ignorees ?? 0) + '</strong></div>'
                    + '<div class="indicateur"><span>En erreur</span><strong id="sync-erreurs" class="text-danger">' + (b.erreurs || []).length + '</strong></div>'
                    + '</div>'
                    + (lignes ? '<ul class="small mb-2">' + lignes + '</ul>' : '')
                    + (erreurs ? '<div class="small text-danger fw-semibold">Points à vérifier (aucune souscription créée pour ces cas) :</div><ul class="small">' + erreurs + '</ul>' : '')
                );
                bootstrap.Modal.getOrCreateInstance(document.getElementById('syncModal')).show();
            }).fail(xhr => window.showToast(erreur(xhr), 'error'))
              .always(() => $btn.prop('disabled', false));
        });
    });
    $('#sync-fermer, #syncModal .btn-close').on('click', () => window.location.reload());

    // Paiements liés à une souscription
    $(document).on('click', '.btn-paiements', function () {
        $('#paiements-contenu').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i></div>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('paiementsModal')).show();
        $.get(URL_SOUSCRIPTIONS + '/' + $(this).data('id') + '/paiements')
            .done(html => $('#paiements-contenu').html(html))
            .fail(xhr => $('#paiements-contenu').html('<div class="text-danger">' + erreur(xhr) + '</div>'));
    });

    // Création
    $('#sousc-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#souscModal').modal('hide');
                window.showToastThenReload(res.message || 'Souscription enregistrée.', 'success');
            },
            error: function (xhr) { window.showToast(erreur(xhr), 'error'); }
        });
    });

    // Remise
    $(document).on('click', '.btn-edit-remise', function () {
        $('#remise-eleve').text($(this).data('eleve'));
        $('#remise-taux').val($(this).data('taux'));
        $('#remise-form').attr('action', URL_SOUSCRIPTIONS + '/' + $(this).data('id'));
    });

    $('#remise-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#remiseModal').modal('hide');
                window.showToastThenReload(res.message || 'Remise mise à jour.', 'success');
            },
            error: function (xhr) { window.showToast(erreur(xhr), 'error'); }
        });
    });
});
</script>
@endpush
