{{-- resources/views/domain/finances/depenses/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Dépenses')
@section('page_icon', 'fa-file-invoice-dollar')
@section('page_title', 'Dépenses')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Dépenses</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.depenses.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('depenses.creer')
        @if ($aGenerer['nb'] > 0)
            <x-bouton variante="contour" type="button" id="btn-generer-depenses" class="btn-sm rounded-pill px-3"
                      data-nb="{{ $aGenerer['nb'] }}" data-montant="{{ number_format($aGenerer['montant'], 0, ',', ' ') }}"
                      title="Décaissements de caisse de l'année sans dépense : leur créer une dépense déjà décaissée">
                <i class="fas fa-magic me-1"></i> Générer depuis les décaissements ({{ $aGenerer['nb'] }})
            </x-bouton>
        @endif
        <x-bouton variante="nouveau" type="button" id="btnNouvelleDepense" data-bs-toggle="modal" data-bs-target="#depenseModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle dépense
        </x-bouton>
    @endcan

    <x-bouton variante="contour" href="{{ route('finances.depenses.exportPdf', request()->query()) }}" class="btn-sm rounded-pill px-3">
    <i class="fas fa-file-pdf me-1"></i> PDF
</x-bouton>
@endsection

@section('css')
<style>
    .depenses-page { --dp-bg-soft:var(--eim-cream); --dp-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--dp-border); border-radius:12px; padding:1.05rem 1.15rem; display:flex; align-items:center; gap:.9rem; transition: transform .2s; box-shadow: 0 2px 8px rgba(142,18,22,.04); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; line-height:1.2; color:var(--school-ink); }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow: 0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--dp-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--dp-border); padding:.55rem .9rem; font-size:.88rem; }

    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }

    .compteur-badge { display:inline-block; background:var(--dp-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--dp-border); }

    .btn-nouveau:hover { color:#fff; transform: translateY(-2px); }
    .btn-outline-scolarite { border:1px solid var(--dp-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--dp-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-depenses thead tr { background: var(--dp-bg-soft); }
    .table-depenses thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--dp-border); padding:.9rem 1rem; white-space:nowrap; }
    .table-depenses thead th i { color: var(--school-gold-dark); }
    .table-depenses tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--dp-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-depenses tbody tr:hover { background:var(--eim-hover); }

    .depense-libelle { font-weight:700; color: var(--school-ink); display:block; }
    .depense-benef   { font-size:.75rem; color: var(--school-muted); }
    .depense-montant { font-variant-numeric: tabular-nums; font-weight:700; text-align:right; }

    .badge-depense { display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:600; padding:.4em .85em; border-radius:999px; }
    .badge-depense-attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-depense-validee { background:#e3f7ec; color:var(--eim-success); }
    .badge-depense-rejetee { background:#fdecea; color:var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; }
    .badge-depense-attente .badge-dot { background: var(--school-gold-dark); }
    .badge-depense-validee .badge-dot { background:var(--eim-success); }
    .badge-depense-rejetee .badge-dot { background:var(--school-red); }

    .empty-state { padding: 3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-depenses { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-depenses { background: linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background: var(--dp-bg-soft); border:1px solid var(--dp-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--school-red); margin-bottom:.75rem; display:flex; align-items:center; gap:6px; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--dp-border); padding:.55rem .75rem; font-size:.88rem; }

    .btn-annuler { background:#fff; border:1px solid var(--dp-border); color:var(--eim-ink-soft); border-radius:8px; font-weight:500; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-annuler:hover { background: var(--dp-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
    .btn-enregistrer:hover { color:#fff; }
</style>
@endsection

@section('contenu')
<div class="depenses-page">

    {{-- Cartes stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-hourglass-half" couleur="gold">{{ $compteurs['attente'] }}<x-slot:libelle>En attente — {{ number_format($montants['attente'], 0, ',', ' ') }}</x-slot:libelle></x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-check-circle" couleur="green">{{ $compteurs['validee'] }}<x-slot:libelle>Validées — {{ number_format($montants['validee'], 0, ',', ' ') }}</x-slot:libelle></x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-times-circle" couleur="red">{{ $compteurs['rejetee'] }}<x-slot:libelle>Rejetées — {{ number_format($montants['rejetee'], 0, ',', ' ') }}</x-slot:libelle></x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="blue" libelle="Total engagé (hors rejets)">{{ number_format($montants['attente'] + $montants['validee'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    {{-- Tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Libellé, bénéficiaire..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['statut'] ?? '') == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="caisse_id" class="form-select filtre-input">
                        <option value="">— Caisse —</option>
                        @foreach ($caisses as $c)
                            <option value="{{ $c->id }}" @selected(($filters['caisse_id'] ?? '') == $c->id)>
                                {{ $c->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input"
                           value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input"
                           value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-depenses align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-tag me-2"></i>Dépense</th>
                            <th><i class="fas fa-calendar me-2"></i>Date</th>
                            <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                            <th><i class="fas fa-cash-register me-2"></i>Caisse de règlement</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Montant</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($depenses as $depense)
                        <tr>
                            <td class="ps-4">
                                <span class="depense-libelle">{{ $depense->libelle }}</span>
                                <span class="depense-benef">
                                    <i class="fas fa-user me-1"></i>{{ $depense->beneficiaire ?? '—' }}
                                </span>
                                <span class="depense-benef type-depense">
                                    @if ($depense->ligneBudgetaire)
                                        <i class="fas fa-chart-line me-1"></i>Ligne : {{ $depense->ligneBudgetaire->libelle }}
                                    @else
                                        <i class="fas fa-minus me-1"></i>Hors ligne budgétaire
                                    @endif
                                </span>
                            </td>
                            <td class="text-sm">{{ optional($depense->date_depense)->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @php $s = $depense->statut_depense; @endphp
                                <span class="badge-depense badge-depense-{{ $s?->css() }}">
                                    <span class="badge-dot"></span>{{ $depense->isReglee() ? 'Décaissée' : ($s?->label() ?? '—') }}
                                </span>
                            </td>
                            <td class="text-sm">
                                @if ($depense->caisse)
                                    <i class="fas fa-cash-register me-1 text-muted"></i>{{ $depense->caisse->libelle }}
                                    <div class="text-xs text-muted">
                                        le {{ optional($depense->date_reglement)->format('d/m/Y') }}
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="depense-montant">
                                {{ number_format((float) $depense->montant, 2, ',', ' ') }}
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.depenses.show', $depense) }}">
                                                <i class="fas fa-eye"></i> Détail &amp; ventilation
                                            </a>
                                        </li>
                                        @if ($depense->justificatif_path)
                                            <li>
                                                <a class="dropdown-item"
                                                   href="{{ asset('storage/' . $depense->justificatif_path) }}"
                                                   target="_blank">
                                                    <i class="fas fa-paperclip"></i> Voir le justificatif
                                                </a>
                                            </li>
                                        @endif

                                        @if ($depense->isEnAttente())
                                            @can('depenses.valider')
                                                <li>
                                                    <button type="button" class="dropdown-item btn-valider"
                                                            data-id="{{ $depense->id }}"
                                                            data-libelle="{{ $depense->libelle }}"
                                                            data-bs-toggle="modal" data-bs-target="#validerModal">
                                                        <i class="fas fa-check"></i> Valider
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger btn-rejeter"
                                                            data-id="{{ $depense->id }}"
                                                            data-libelle="{{ $depense->libelle }}"
                                                            data-bs-toggle="modal" data-bs-target="#rejeterModal">
                                                        <i class="fas fa-times"></i> Rejeter
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif

                                        @if ($depense->peutEtreReglee())
                                            @can('depenses.regler')
                                                <li>
                                                    <button type="button" class="dropdown-item btn-regler"
                                                            data-id="{{ $depense->id }}"
                                                            data-libelle="{{ $depense->libelle }}"
                                                            data-montant="{{ number_format((float) $depense->montant, 2, ',', ' ') }}"
                                                            data-bs-toggle="modal" data-bs-target="#reglerModal">
                                                        <i class="fas fa-cash-register"></i> Régler via caisse
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif

                                        @if ($depense->isRejetee() && $depense->motif_rejet)
                                            <li>
                                                <span class="dropdown-item text-muted small" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ \Str::limit($depense->motif_rejet, 40) }}
                                                </span>
                                            </li>
                                        @endif

                                        @if (! $depense->isReglee() || ($depense->caisse && $depense->caisse->isOuverte()))
    @can('depenses.creer')
        <li><hr class="dropdown-divider"></li>
        <x-actions.supprimer :action="route('finances.depenses.destroy', $depense)" data-confirm-title="Supprimer cette dépense ?" data-confirm-text="Le mouvement de caisse associé sera également retiré. Cette action peut être annulée.">Supprimer</x-actions.supprimer>
    @endcan
@endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="6" icone="fas fa-file-invoice-dollar" :paragraphe="false"><p class="mb-0 fw-medium">Aucune dépense enregistrée.</p></x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4">
        <x-pagination :liste="$depenses->appends(request()->query())" />
    </div>

    {{-- ========= Modale nouvelle dépense ========= --}}
    @can('depenses.creer')
    <x-modale id="depenseModal" data-bs-backdrop="static" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-depenses" entete-class="modal-header-depenses" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="depense-form" method="POST" enctype="multipart/form-data" action="{{ route('finances.depenses.store') }}">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-file-invoice-dollar me-2"></i>Nouvelle dépense</x-slot:titre>
                        <x-section titre="Informations" icone="fas fa-info-circle" class="mb-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <x-libelle requis>Libellé</x-libelle>
                                    <input type="text" name="libelle" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <x-libelle requis>Bénéficiaire</x-libelle>
                                    <input type="text" name="beneficiaire" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="beneficiaire-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <x-libelle requis>Date</x-libelle>
                                    <input type="date" name="date_depense" class="form-control"
                                           value="{{ now()->toDateString() }}">
                                    <div class="text-danger text-xs mt-1" id="date_depense-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <x-libelle requis>Montant</x-libelle>
                                    <input type="number" name="montant" step="0.01" min="0.01" class="form-control">
                                    <div class="text-danger text-xs mt-1" id="montant-error"></div>
                                </div>
                                <div class="col-12">
                                    <x-libelle requis>Type de dépense</x-libelle>
                                    <div class="d-flex flex-wrap gap-3" id="choix-imputation">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="imputation" id="imputation-hors-ligne" value="hors_ligne" checked>
                                            <label class="form-check-label" for="imputation-hors-ligne">Dépense hors ligne budgétaire</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="imputation" id="imputation-ligne" value="ligne">
                                            <label class="form-check-label" for="imputation-ligne">Dépense sur ligne budgétaire</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-none" id="bloc-ligne-budgetaire">
                                    <x-libelle requis>Ligne budgétaire</x-libelle>
                                    <select name="ligne_budgetaire_id" id="depense-ligne-budgetaire" class="form-select">
                                        <option value="">— Choisir la ligne —</option>
                                        @foreach ($lignesBudgetaires as $lb)
                                            <option value="{{ $lb->id }}">{{ $lb->libelle }} ({{ number_format((float) $lb->montant_previsionnel, 0, ',', ' ') }} F)</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text small">La dépense sera imputée à la ligne une fois réellement décaissée.</div>
                                    <div class="text-danger text-xs mt-1" id="ligne_budgetaire_id-error"></div>
                                </div>
                                <div class="col-12">
                                    <x-libelle>Motif</x-libelle>
                                    <textarea name="motif_depense" rows="2" class="form-control"
                                              maxlength="1000"></textarea>
                                </div>
                                <div class="col-12">
                                    <x-libelle>Justificatif (PDF, image, doc — 5 Mo max)</x-libelle>
                                    <input type="file" name="justificatif" class="form-control"
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                </div>

                                {{-- Pas de nature analytique à la saisie : la dépense est ventilée ensuite sur les centres analytiques --}}
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-text small" id="aide-ventilation">
                                        <i class="fas fa-sitemap me-1"></i>La ventilation analytique se fait ensuite, depuis le détail de la dépense, sur les centres analytiques créés.
                                    </div>
                                </div>
                            </div>
                        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">
                <i class="fas fa-save me-1"></i> Enregistrer
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- ========= Modale validation ========= --}}
    @can('depenses.valider')
    <x-modale id="validerModal" aria-hidden="true" contenu-class="modal-depenses" entete-class="modal-header-depenses" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="valider-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-check me-2"></i>Valider la dépense</x-slot:titre>
        <p>Confirmer la validation de la dépense <strong id="valider-libelle"></strong> ?</p>
        <p class="text-muted small mb-0">
            Une fois validée, la dépense pourra être rattachée à une caisse pour règlement.
        </p>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">Valider</x-bouton>
        </x-slot:pied>
    </x-modale>

    {{-- ========= Modale rejet ========= --}}
    <x-modale id="rejeterModal" aria-hidden="true" contenu-class="modal-depenses" entete-style="background: linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark)); color:#fff; border-radius:16px 16px 0 0;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="rejeter-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-times me-2"></i>Rejeter la dépense</x-slot:titre>
        <p>Rejeter la dépense <strong id="rejeter-libelle"></strong> ?</p>
        <x-section>
            <x-libelle requis>Motif du rejet</x-libelle>
            <textarea name="motif_rejet" rows="3" class="form-control" required minlength="5"></textarea>
            <div class="text-danger text-xs mt-1" id="motif_rejet-error"></div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <button type="submit" class="btn" style="background: var(--school-red-dark); color:#fff; border-radius:8px; font-weight:600; padding:.55rem 1.4rem;">
                <i class="fas fa-times me-1"></i> Rejeter
            </button>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- ========= Modale règlement ========= --}}
    @can('depenses.regler')
    <x-modale id="reglerModal" aria-hidden="true" contenu-class="modal-depenses" entete-class="modal-header-depenses" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="regler-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-cash-register me-2"></i>Régler la dépense</x-slot:titre>
        <div class="d-flex justify-content-between align-items-center mb-3 p-3"
             style="background: var(--dp-bg-soft); border-radius:10px;">
            <div>
                <div class="text-muted text-xs">Dépense</div>
                <div class="fw-semibold" id="regler-libelle">—</div>
            </div>
            <div class="text-end">
                <div class="text-muted text-xs">Montant</div>
                <div class="fw-bold fs-6" id="regler-montant">—</div>
            </div>
        </div>

        <x-section>
            <x-libelle requis>Caisse de règlement</x-libelle>
            <select name="caisse_id" class="form-select" required>
                <option value="">— Sélectionner une caisse ouverte —</option>
                @foreach ($caisses as $c)
                    @if ($c->statut === \App\Domain\Finances\Types\CaisseStatut::OUVERTE)
                        <option value="{{ $c->id }}">{{ $c->libelle }}</option>
                    @endif
                @endforeach
            </select>
            <div class="text-danger text-xs mt-1" id="caisse_id-error"></div>
            <div class="form-text small mt-2">
                <i class="fas fa-info-circle me-1"></i>
                Un mouvement de sortie sera automatiquement ajouté au journal de la caisse.
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">
                <i class="fas fa-check me-1"></i> Régler
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

</div>
@endsection

@push('js')
<script>
$(function () {
    // Dépense sur ligne budgétaire : la ligne devient obligatoire
    const majImputation = function () {
        const surLigne = $('#imputation-ligne').is(':checked');
        $('#bloc-ligne-budgetaire').toggleClass('d-none', !surLigne);
        $('#depense-ligne-budgetaire').prop('required', surLigne);
        if (!surLigne) $('#depense-ligne-budgetaire').val('');
    };
    $(document).on('change', '[name=imputation]', majImputation);
    $('#depenseModal').on('show.bs.modal', function () { setTimeout(majImputation, 0); });

    // Générer les dépenses des décaissements de caisse qui n'en ont pas
    $('#btn-generer-depenses').on('click', function () {
        const $btn = $(this);
        if (!confirm(`Créer ${$btn.data('nb')} dépense(s) (${$btn.data('montant')} F) à partir des décaissements de caisse qui n'en ont pas ?\n\nElles seront enregistrées comme décaissées, dans la caisse utilisée.`)) return;
        $btn.prop('disabled', true);
        $.ajax({ url: '{{ route('finances.depenses.generer-depuis-decaissements') }}', method: 'POST',
                 data: { _token: '{{ csrf_token() }}' }, headers: { 'Accept': 'application/json' } })
            .done(res => {
                const b = res.bilan || res.data?.bilan || {};
                window.showToastThenReload(`${b.creees ?? 0} dépense(s) générée(s) — ${new Intl.NumberFormat('fr-FR').format(b.montant ?? 0)} F.`, 'success');
            })
            .fail(xhr => { $btn.prop('disabled', false); window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error'); });
    });
});
</script>
<script>
$(function () {
    // ========= Saisie =========
    const $form = $('#depense-form');
    if ($form.length) {
        $form.on('submit', function (e) {
            e.preventDefault();
            clearErrors($form);

            const fd = new FormData(this);
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#depenseModal').modal('hide');
                    window.showToastThenReload(res.message || 'Dépense enregistrée.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors(xhr.responseJSON.errors);
                    } else {
                        window.showToast("Une erreur est survenue.", 'error');
                    }
                }
            });
        });
    }

    // ========= Validation =========
    $('.btn-valider').on('click', function () {
        const id = $(this).data('id');
        $('#valider-libelle').text($(this).data('libelle'));
        $('#valider-form').attr('action', `{{ url('finances/depenses') }}/${id}/valider`);
    });
    $('#valider-form').on('submit', function (e) {
        e.preventDefault();
        ajaxForm($(this), 'validerModal');
    });

    // ========= Rejet =========
    $('.btn-rejeter').on('click', function () {
        const id = $(this).data('id');
        $('#rejeter-libelle').text($(this).data('libelle'));
        $('#rejeter-form').attr('action', `{{ url('finances/depenses') }}/${id}/rejeter`);
    });
    $('#rejeter-form').on('submit', function (e) {
        e.preventDefault();
        ajaxForm($(this), 'rejeterModal');
    });

    // ========= Règlement =========
    $('.btn-regler').on('click', function () {
        const id = $(this).data('id');
        $('#regler-libelle').text($(this).data('libelle'));
        $('#regler-montant').text($(this).data('montant'));
        $('#regler-form').attr('action', `{{ url('finances/depenses') }}/${id}/regler`);
    });
    $('#regler-form').on('submit', function (e) {
        e.preventDefault();
        ajaxForm($(this), 'reglerModal');
    });

    // ========= Helpers =========
    function ajaxForm($f, modalId) {
        clearErrors($f);
        $.ajax({
            url: $f.attr('action'),
            method: 'POST',
            data: $f.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#' + modalId).modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    }

    function clearErrors($f) {
        $f.find('.is-invalid').removeClass('is-invalid');
        $f.find('[id$="-error"]').text('');
    }

    function showErrors(errors) {
        Object.keys(errors).forEach(function (field) {
            $('[name="' + field + '"]').addClass('is-invalid');
            $('#' + field + '-error').text(errors[field][0]);
        });
    }
});
</script>
@endpush