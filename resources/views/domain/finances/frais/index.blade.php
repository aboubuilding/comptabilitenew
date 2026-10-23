{{-- Frais scolaires : cinq onglets, tous bornés à l'année scolaire de l'en-tête --}}
@extends('admin.layouts.app')

@section('title', 'Frais scolaires')
@section('page_icon', 'fa-tags')
@section('page_title', 'Frais scolaires & Échéanciers')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances</li>
    <li class="breadcrumb-item active">Frais scolaires</li>
@endsection

@section('page_actions')
    @php $requete = ['onglet' => $onglet] + request()->except(['page', 'onglet']); @endphp
    <x-bouton variante="contour" href="{{ route('finances.plan-echeanciers.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-calendar-alt me-1"></i> Plans d'échéancier
    </x-bouton>
    <div class="dropdown">
        <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3 dropdown-toggle" id="btn-exporter" data-bs-toggle="dropdown">
            <i class="fas fa-file-export me-1"></i> Exporter
        </x-bouton>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" id="export-csv" href="{{ route('finances.frais-ecoles.export', $requete) }}"><i class="fas fa-file-excel me-2 text-success"></i>Excel (CSV)</a></li>
            <li><a class="dropdown-item" id="export-pdf" href="{{ route('finances.frais-ecoles.exportPdf', $requete) }}"><i class="fas fa-file-pdf me-2 text-danger"></i>PDF</a></li>
        </ul>
    </div>
    @can('frais.gerer')
        <div class="dropdown">
            <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3 dropdown-toggle" id="btn-synchroniser" data-bs-toggle="dropdown">
                <i class="fas fa-sync-alt me-1"></i> Synchroniser
            </x-bouton>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach ($cibles as $cle => $libelle)
                    <li><button type="button" class="dropdown-item btn-sync-cible" data-cible="{{ $cle }}" data-libelle="{{ $libelle }}">
                        <i class="fas {{ ['produits' => 'fa-box', 'activites' => 'fa-futbol', 'evenements' => 'fa-calendar-check'][$cle] }} me-2"></i>{{ $libelle }}
                    </button></li>
                @endforeach
            </ul>
        </div>
        <x-bouton variante="nouveau" type="button" id="btnNouveauFrais" data-bs-toggle="modal" data-bs-target="#fraisModal">
            <i class="fas fa-plus-circle me-1"></i> Définir un frais
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .frais-page { --frais-bg-soft: var(--eim-cream); --frais-border: var(--eim-border); }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(142,18,22,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--frais-border) !important; }

    .btn-outline-scolarite { border: 1px solid var(--frais-border); color: var(--school-ink); background: #fff; font-size: .85rem; }
    .btn-outline-scolarite:hover { background: var(--frais-bg-soft); border-color: var(--school-red); color: var(--school-red); }

    .filtre-label { font-size: .74rem; font-weight: 600; color: var(--eim-ink-soft); text-transform: uppercase; letter-spacing: .03em; margin-bottom: 5px; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--frais-border); padding: .5rem .8rem; font-size: .85rem; }
    .search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #7A6A6A; font-size: .8rem; }
    .search-input { padding-left: 32px !important; }

    .btn-filtrer { background: var(--school-red); color: #fff; border: none; border-radius: 10px; padding: .5rem 1rem; font-weight: 600; font-size: .85rem; }

    .compteur-badge {
        display: inline-block; background: var(--frais-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .82rem; padding: .4rem .85rem; border-radius: 999px; border: 1px solid var(--frais-border);
    }

    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(227,30,36,.35); }

    .table-frais thead tr { background: var(--frais-bg-soft); }
    .table-frais thead th {
        color: var(--school-red); font-size: .74rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--frais-border); padding: .85rem .9rem;
    }
    .table-frais tbody td { padding: .8rem .9rem; border-bottom: 1px solid var(--frais-border); font-size: .87rem; color: var(--school-ink); vertical-align: middle; }
    .table-frais tbody tr:hover { background: var(--eim-hover); }
    .table-frais tbody tr:last-child td { border-bottom: none; }

    .badge-forfait { display: inline-flex; align-items: center; font-size: .74rem; font-weight: 600; padding: .35em .8em; border-radius: 999px; background: var(--frais-bg-soft); color: var(--school-red); }
    .badge-paiement { display: inline-flex; align-items: center; font-size: .74rem; font-weight: 600; padding: .35em .8em; border-radius: 999px; }
    .badge-paiement-comptant { background: #e3f7ec; color: var(--eim-success); }
    .badge-paiement-echelonne { background: #fdf3e2; color: #b8720b; }

    .montant-frais { font-weight: 700; color: var(--school-red-dark); }
    .plan-nom { font-size: .84rem; }
    .plan-absent { color: var(--school-muted); font-size: .84rem; }


    .modal-frais { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-frais { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
    .modal-header-frais .modal-title { font-weight: 600; font-size: 1.05rem; }
    .form-section { background: var(--frais-bg-soft); border: 1px solid var(--frais-border); border-radius: 12px; padding: 1rem 1.1rem; }
    .form-section .form-control, .form-section .form-select { border-radius: 8px; border: 1px solid var(--frais-border); }
    .btn-annuler { background: #fff; border: 1px solid var(--frais-border); color: var(--eim-ink-soft); border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--frais-bg-soft); }

    /* .btn-action / .dropdown-menu-actions viennent de layouts/app.blade.php */

    .text-xs { font-size:.72rem; }
    .onglets { border-bottom:2px solid var(--frais-border, var(--eim-border)); gap:.25rem; flex-wrap:nowrap; overflow-x:auto; }
    .onglets .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.9rem; padding:.75rem 1rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .onglets .compte { display:inline-block; min-width:22px; padding:0 .45rem; border-radius:999px; background:var(--eim-cream); color:var(--school-red); font-size:.72rem; margin-left:.35rem; border:1px solid var(--eim-border); }
    .badge-nature { display:inline-block; background:var(--eim-cream); color:var(--school-red); font-weight:600; font-size:.72rem; padding:.2rem .65rem; border-radius:999px; border:1px solid var(--eim-border); }
    th a.tri { color:inherit; text-decoration:none; }
    th a.tri:hover { text-decoration:underline; }
    #echeances-bloc { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.75rem; }
    #echeances-table td { padding:.25rem .3rem; vertical-align:middle; }
    #echeances-table input { font-size:.85rem; }
    .echeances-total.ecart { color:var(--school-red); font-weight:700; }
    .echeances-total.juste { color:var(--eim-success); font-weight:700; }
    .indicateurs { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:.6rem; }
    .indicateur { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.55rem .8rem; }
    .indicateur span { display:block; font-size:.66rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .indicateur strong { font-size:1rem; }
</style>
@endsection

@section('contenu')
@php
    $tri = fn (string $colonne) => route('finances.frais-ecoles.index', ['onglet' => $onglet, 'tri' => $colonne,
        'sens' => ($filters['tri'] ?? '') === $colonne && ($filters['sens'] ?? 'asc') === 'asc' ? 'desc' : 'asc']
        + request()->except(['page', 'onglet', 'tri', 'sens']));
    $colonnes = 5;
@endphp
    <div class="frais-page">
        <div class="d-flex align-items-center gap-2 mb-3" id="annee-frais">
            <span class="badge-nature" style="font-size:.8rem; padding:.35rem .8rem;"><i class="fas fa-calendar-alt me-1"></i>Année scolaire : {{ $annee?->libelle }}</span>
            <span class="text-xs text-muted">{{ $compteurs['comptant'] }} comptant · {{ $compteurs['echelonne'] }} échelonné(s)</span>
        </div>

        <x-carte>
            <ul class="nav onglets px-3 pt-2" id="onglets-frais">
                @foreach ($onglets as $cle => [$libelle])
                    <li class="nav-item">
                        <a class="nav-link @if ($onglet === $cle) active @endif" id="onglet-{{ $cle }}" href="{{ route('finances.frais-ecoles.index', ['onglet' => $cle]) }}">
                            {{ $libelle }} <span class="compte">{{ $compteursOnglets[$cle] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($encaisseType)
                {{-- Encaissé d'après le type de paiement des lignes actives (Direction et Administrateur) --}}
                <div class="px-4 pt-3 small" id="encaisse-type">
                    <i class="fas fa-coins me-1" style="color:var(--school-gold-dark);"></i>
                    Encaissé en {{ $annee?->libelle }} pour ce type de paiement :
                    <strong id="encaisse-type-total">{{ number_format($encaisseType['total'], 0, ',', ' ') }} F</strong>
                    @if ($encaisseType['lignes_sans_frais'] > 0)
                        — dont <strong class="text-danger" id="encaisse-type-sans-frais">{{ number_format($encaisseType['sans_frais'], 0, ',', ' ') }} F</strong>
                        sur {{ $encaisseType['lignes_sans_frais'] }} paiement(s) qui ne citent aucun frais de la liste (ils ne figurent donc sur aucune ligne ci-dessous).
                    @endif
                </div>
            @endif

            <div class="card-header bg-white py-3 px-4">
                <form method="GET" class="row g-2 align-items-end" id="filtres-frais">
                    <input type="hidden" name="onglet" value="{{ $onglet }}">
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Niveau</label>
                        <select name="niveau_id" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach ($niveauxOptions as $niveau)
                                <option value="{{ $niveau->id }}" @selected(($filters['niveau_id'] ?? '') == $niveau->id)>{{ $niveau->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($onglet === 'tous')
                        <div class="col-6 col-md-2">
                            <label class="filtre-label">Type de frais</label>
                            <select name="type_paiement" class="form-select filtre-input">
                                <option value="">Tous</option>
                                @foreach ($toutesNatures as $val => $label)
                                    <option value="{{ $val }}" @selected(($filters['type_paiement'] ?? '') == $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Modalité</label>
                        <select name="modalite_paiement" class="form-select filtre-input">
                            <option value="">Toutes</option>
                            @foreach ($modalitesPaiement as $val => $label)
                                <option value="{{ $val }}" @selected(($filters['modalite_paiement'] ?? '') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filtre-label">Recherche</label>
                        <x-recherche name="search" placeholder="Libellé..." value="{{ $filters['search'] ?? '' }}" />
                    </div>
                    <div class="col-6 col-md-2 d-flex gap-1">
                        <x-bouton variante="filtrer" type="submit" class="flex-fill" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                        <x-bouton variante="contour" href="{{ route('finances.frais-ecoles.index', ['onglet' => $onglet]) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                    </div>
                    <div class="col-6 col-md-1 text-end">
                        <x-compteur>{{ $fraisEcoles->total() }}</x-compteur>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <x-tableau class="table-frais align-middle mb-0" id="table-frais">
                    <thead>
                        <tr>
                            <th>
                                <a class="tri" href="{{ $tri('libelle') }}">Frais</a>
                                <span class="fw-normal text-muted" style="text-transform:none;">· <a class="tri" href="{{ $tri('type') }}">type</a></span>
                            </th>
                            <th><a class="tri" href="{{ $tri('niveau') }}">Niveau</a></th>
                            <th class="text-end">
                                <a class="tri" href="{{ $tri('montant') }}">Montant</a>
                                @if ($recoltes !== null)
                                    <span class="fw-normal text-muted" style="text-transform:none;" id="col-recolte"
                                          title="Total encaissé sur ce frais. Un paiement est rattaché à l'année où le frais a été créé, même s'il a été versé une autre année.">· récolté</span>
                                @endif
                            </th>
                            <th>Échéancier</th>
                            <th class="text-center" style="width:70px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fraisEcoles as $frais)
                            <tr>
                                <td>
                                    <strong>{{ $frais->libelle }}</strong>
                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                        <span class="badge-nature">{{ $frais->type_paiement?->label() ?? '—' }}</span>
                                        @if ($frais->type_forfait)
                                            <span class="badge-forfait">{{ $frais->type_forfait->label() }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $frais->niveau?->libelle ?? 'Tous niveaux' }}</td>
                                <td class="text-end">
                                    <span class="montant-frais">{{ number_format($frais->montant ?? 0, 0, ',', ' ') }} F</span>
                                    @if ($recoltes !== null)
                                        <div class="text-xs recolte">
                                            @if (($recoltes[$frais->id] ?? null) === null)
                                                <span class="text-muted" title="Les paiements de cette nature ne citent pas le frais et plusieurs frais valent pour ce niveau : le montant ne peut pas être attribué.">récolté : —</span>
                                            @else
                                                <span class="text-muted">récolté</span> <span class="fw-bold text-success">{{ number_format($recoltes[$frais->id], 0, ',', ' ') }} F</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if ($frais->planEcheancier)
                                        <span class="plan-nom">{{ $frais->planEcheancier->nom }}</span>
                                        <div class="text-xs text-muted">Échelonné</div>
                                    @elseif ($frais->modalite_paiement === \App\Domain\Finances\Types\TypePaiement::COMPTANT)
                                        <span class="plan-absent">Comptant</span>
                                    @else
                                        <span class="plan-absent">Aucun</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.frais-ecoles.show', $frais) }}">
                                                <i class="fas fa-eye"></i> Voir le détail
                                            </a>
                                        </li>
                                        @can('frais.gerer')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-frais" data-id="{{ $frais->id }}"
                                                        data-bs-toggle="modal" data-bs-target="#fraisModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-actions.supprimer :action="route('finances.frais-ecoles.destroy', $frais)" icone="fas fa-box-archive"
                                                data-confirm-title="Archiver ce frais ?" data-confirm-text="Il ne sera plus proposé ; les paiements déjà enregistrés sont conservés.">Archiver</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                                </td>
                            </tr>
                        @empty
                            <x-vide colspan="{{ $colonnes }}" icone="fas fa-tags" :paragraphe="false"><p class="mb-0">Aucun frais ne correspond à ces critères pour {{ $annee?->libelle }}.</p></x-vide>
                        @endforelse
                    </tbody>
                </x-tableau>
            </div>
        </x-carte>
        <div class="mt-3">
            <x-pagination :liste="$fraisEcoles->appends(request()->query())" />
        </div>

        @can('frais.gerer')
        {{-- Fenêtre unique : ajout ET modification. Seuls l'inscription, l'assurance et la scolarité se créent ici. --}}
        <x-modale id="fraisModal" data-bs-backdrop="static" aria-hidden="true" dialogue="modal-lg" contenu-class="modal-frais" entete-class="modal-header-frais" corps-class="p-4" croix-blanche>
            <x-slot:formulaire id="frais-form">@csrf
                <input type="hidden" name="_method" id="frais-method" value="POST"></x-slot:formulaire>
            <x-slot:titre id="frais-modal-title"><i class="fas fa-plus-circle me-2"></i>Définir un frais</x-slot:titre>
            <x-section>
                <div class="text-xs text-muted mb-2" id="frais-annee-info"><i class="fas fa-calendar-alt me-1"></i>Ce frais est défini pour l'année scolaire {{ $annee?->libelle }}.</div>
                <div class="mb-3">
                    <x-libelle requis>Libellé</x-libelle>
                    <input type="text" name="libelle" id="frais-libelle" class="form-control" placeholder="Ex : Frais de scolarité CM1">
                    <div class="text-danger small mt-1" id="frais-libelle-error"></div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <x-libelle requis>Type de frais</x-libelle>
                        <select name="type_paiement" id="frais-type-paiement" class="form-select">
                            @foreach ($naturesCreables as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text text-xs">Cantine, transport, activités et événements se définissent dans leurs modules.</div>
                        <div class="text-danger small mt-1" id="frais-type_paiement-error"></div>
                    </div>
                    <div class="col-md-6">
                        <x-libelle requis>Nature</x-libelle>
                        <select name="type_forfait" id="frais-type-forfait" class="form-select">
                            @foreach ($typesForfait as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <x-libelle>Niveau</x-libelle>
                        <select name="niveau_id" id="frais-niveau" class="form-select">
                            <option value="">-- Tous niveaux --</option>
                            @foreach ($niveauxOptions as $niveau)
                                <option value="{{ $niveau->id }}">{{ $niveau->libelle }}</option>
                            @endforeach
                        </select>
                        <div class="text-danger small mt-1" id="frais-niveau_id-error"></div>
                    </div>
                    <div class="col-md-6">
                        <x-libelle requis>Montant total</x-libelle>
                        <input type="number" step="0.01" name="montant" id="frais-montant" class="form-control">
                        <div class="text-danger small mt-1" id="frais-montant-error"></div>
                    </div>
                    <div class="col-md-6">
                        <x-libelle requis>Modalité de paiement</x-libelle>
                        <select name="modalite_paiement" id="frais-modalite-paiement" class="form-select">
                            @foreach ($modalitesPaiement as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 bloc-echelonne">
                        <x-libelle>Échéancier</x-libelle>
                        <select name="plan_echeancier_id" id="frais-plan" class="form-select">
                            <option value="">Saisir les échéances ci-dessous</option>
                            @foreach ($plansOptions as $plan)
                                <option value="{{ $plan->id }}">Plan existant : {{ $plan->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Échéancier défini avec le frais (paiement échelonné) --}}
                <div class="bloc-echelonne mt-3" id="echeances-bloc">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <strong class="small"><i class="fas fa-list-ol me-1"></i>Échéances</strong>
                        <div class="ms-auto d-flex align-items-center gap-1">
                            <input type="number" min="1" max="24" value="3" id="echeances-nombre" class="form-control form-control-sm" style="width:70px;" title="Nombre d'échéances">
                            <button type="button" class="btn btn-sm btn-outline-scolarite" id="echeances-repartir"><i class="fas fa-magic me-1"></i>Répartir</button>
                            <button type="button" class="btn btn-sm btn-outline-scolarite" id="echeances-ajouter"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <table class="table mb-1" id="echeances-table">
                        <thead><tr class="text-xs text-muted"><th style="width:36px;">N°</th><th>Libellé</th><th style="width:160px;">Date d'échéance</th><th style="width:150px;">Montant</th><th style="width:36px;"></th></tr></thead>
                        <tbody></tbody>
                    </table>
                    <div class="text-end small">Total des échéances : <span class="echeances-total" id="echeances-total">0</span> / <span id="echeances-attendu">0</span> F</div>
                    <div class="text-danger small mt-1" id="frais-echeances-error"></div>
                </div>
            </x-section>
            <x-slot:pied>
                <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
                <x-bouton type="submit"><i class="fas fa-save me-1"></i>Enregistrer</x-bouton>
            </x-slot:pied>
        </x-modale>

        {{-- Bilan d'une synchronisation --}}
        <x-modale id="syncModal" contenu-class="modal-frais" entete-class="modal-header-frais" corps-id="sync-contenu" corps-class="p-4" croix-blanche>
            <x-slot:titre id="sync-titre"><i class="fas fa-sync-alt me-2"></i>Synchronisation</x-slot:titre>
            <x-slot:pied>
                <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
            </x-slot:pied>
        </x-modale>
        @endcan
    </div>
@endsection

@push('js')
<script>
$(function () {
    // La fenêtre de saisie n'est rendue que pour qui peut gérer.
    if (!document.getElementById('fraisModal')) return;
    const ECHELONNE = '{{ \App\Domain\Finances\Types\TypePaiement::ECHELONNE->value }}';
    const NATURES = @json($toutesNatures);
    const CREABLES = @json(array_keys($naturesCreables));
    const modal    = new bootstrap.Modal(document.getElementById('fraisModal'));
    const $form    = $('#frais-form');
    const $title   = $('#frais-modal-title');
    const $method  = $('#frais-method');
    const $corps   = $('#echeances-table tbody');
    const texte    = v => $('<i>').text(v ?? '').html();
    const fmt      = v => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v);

    const fields = {
        libelle:       { input: $('#frais-libelle'),       error: $('#frais-libelle-error') },
        niveau_id:     { input: $('#frais-niveau'),        error: $('#frais-niveau_id-error') },
        montant:       { input: $('#frais-montant'),       error: $('#frais-montant-error') },
        type_paiement: { input: $('#frais-type-paiement'), error: $('#frais-type_paiement-error') },
    };

    const storeUrl = '{{ route('finances.frais-ecoles.store') }}';
    let currentId  = null;

    /* ---------------- Échéancier saisi avec le frais ---------------- */
    function ligne(e = {}) {
        return $('<tr>').append(
            '<td class="text-muted small numero"></td>',
            $('<td>').append($('<input type="text" class="form-control form-control-sm ech-libelle" maxlength="150">').val(e.libelle ?? '')),
            $('<td>').append($('<input type="date" class="form-control form-control-sm ech-date">').val(e.date_echeance ?? '')),
            $('<td>').append($('<input type="number" step="0.01" min="0" class="form-control form-control-sm ech-montant text-end">').val(e.montant ?? '')),
            '<td><button type="button" class="btn btn-sm text-danger ech-retirer" title="Retirer"><i class="fas fa-times"></i></button></td>'
        );
    }

    function renumeroter() {
        $corps.find('tr').each(function (i) {
            $(this).find('.numero').text(i + 1);
            $(this).find('.ech-libelle').attr('name', `echeances[${i}][libelle]`).attr('placeholder', 'Échéance ' + (i + 1));
            $(this).find('.ech-date').attr('name', `echeances[${i}][date_echeance]`);
            $(this).find('.ech-montant').attr('name', `echeances[${i}][montant]`);
        });
        totaliser();
    }

    function totaliser() {
        const attendu = parseFloat($('#frais-montant').val()) || 0;
        let total = 0;
        $corps.find('.ech-montant').each(function () { total += parseFloat(this.value) || 0; });
        $('#echeances-total').text(fmt(total)).toggleClass('juste', Math.abs(total - attendu) <= 1 && attendu > 0)
            .toggleClass('ecart', Math.abs(total - attendu) > 1);
        $('#echeances-attendu').text(fmt(attendu));
    }

    function remplir(echeances) {
        $corps.empty();
        (echeances || []).forEach(e => $corps.append(ligne(e)));
        renumeroter();
    }

    // Répartition égale ; la dernière échéance absorbe l'arrondi
    $('#echeances-repartir').on('click', function () {
        const n = Math.max(1, Math.min(24, parseInt($('#echeances-nombre').val(), 10) || 1));
        const total = parseFloat($('#frais-montant').val()) || 0;
        const part = Math.floor(total / n);
        const actuelles = $corps.find('tr').map(function () {
            return { libelle: $(this).find('.ech-libelle').val(), date_echeance: $(this).find('.ech-date').val() };
        }).get();
        remplir(Array.from({ length: n }, (_, i) => ({
            libelle: actuelles[i]?.libelle || '', date_echeance: actuelles[i]?.date_echeance || '',
            montant: i === n - 1 ? total - part * (n - 1) : part,
        })));
    });
    $('#echeances-ajouter').on('click', () => { $corps.append(ligne()); renumeroter(); });
    $corps.on('click', '.ech-retirer', function () { $(this).closest('tr').remove(); renumeroter(); });
    $corps.on('input', '.ech-montant', totaliser);
    $('#frais-montant').on('input', totaliser);

    // L'échéancier n'apparaît que pour un frais échelonné sans plan existant choisi
    function afficherEcheancier() {
        const echelonne = $('#frais-modalite-paiement').val() === ECHELONNE;
        $('.bloc-echelonne').toggle(echelonne);
        const saisie = echelonne && !$('#frais-plan').val();
        $('#echeances-bloc').toggle(saisie);
        // Les champs masqués ne sont pas envoyés
        $('#echeances-bloc input').prop('disabled', !saisie);
        if (!echelonne) $('#frais-plan').val('');
    }
    $('#frais-modalite-paiement, #frais-plan').on('change', afficherEcheancier);

    /* ---------------- Formulaire ---------------- */
    function natures(courante) {
        // En création : les trois natures créables. En modification, la nature du frais reste proposée.
        const valeurs = CREABLES.map(String);
        if (courante && !valeurs.includes(String(courante))) valeurs.unshift(String(courante));
        $('#frais-type-paiement').html(valeurs.map(v => `<option value="${v}">${texte(NATURES[v])}</option>`).join(''));
        if (courante) $('#frais-type-paiement').val(String(courante));
    }

    function clearErrors() {
        Object.values(fields).forEach(f => { f.input.removeClass('is-invalid'); f.error.text(''); });
        $('#frais-echeances-error').text('');
    }

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $method.val('POST');
        natures(null);
        remplir([]);
        clearErrors();
        afficherEcheancier();
    }

    $('#btnNouveauFrais').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Définir un frais');
    });

    $(document).on('click', '.btn-edit-frais', function () {
        resetForm();
        currentId = $(this).data('id');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier le frais');
        $method.val('PUT');

        $.getJSON(`{{ url('finances/frais-ecoles') }}/${currentId}/edit`, function (data) {
            const f = data.fraisEcole;
            $('#frais-libelle').val(f.libelle);
            $('#frais-type-forfait').val(f.type_forfait);
            $('#frais-niveau').val(f.niveau_id);
            $('#frais-montant').val(f.montant);
            natures(f.type_paiement);
            $('#frais-modalite-paiement').val(f.modalite_paiement || $('#frais-modalite-paiement option:first').val());
            // Le plan du frais reste sélectionné ; ses échéances sont prêtes à être ajustées
            // si l'on choisit « Saisir les échéances ci-dessous »
            remplir(f.echeances);
            $('#frais-plan').val(f.plan_echeancier_id || '');
            if (f.plan_echeancier_id && !$('#frais-plan').val()) $('#frais-plan').val('');
            afficherEcheancier();
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();
        afficherEcheancier();

        $.ajax({
            url: currentId ? `{{ url('finances/frais-ecoles') }}/${currentId}` : storeUrl,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function (field) {
                        if (fields[field]) {
                            fields[field].input.addClass('is-invalid');
                            fields[field].error.text(errors[field][0]);
                        } else if (field.startsWith('echeances')) {
                            $('#frais-echeances-error').text(errors[field][0]);
                        }
                    });
                } else if (xhr.responseJSON?.message && $('#echeances-bloc').is(':visible')) {
                    $('#frais-echeances-error').text(xhr.responseJSON.message);
                } else {
                    window.showToast(xhr.responseJSON?.message || "Une erreur est survenue.", 'error');
                }
            }
        });
    });

    /* ---------------- Synchronisation des catalogues (année de l'en-tête) ---------------- */
    $('.btn-sync-cible').on('click', function () {
        const cible = $(this).data('cible'), libelle = $(this).data('libelle');
        Swal.fire({
            title: 'Synchroniser : ' + libelle + ' ?',
            text: "Les fiches manquantes seront créées à partir des frais de l'année {{ $annee?->libelle }}. Rien n'est supprimé ; les paiements ne sont pas modifiés.",
            icon: 'question', showCancelButton: true, confirmButtonColor: '#E31E24',
            confirmButtonText: 'Oui, synchroniser', cancelButtonText: 'Annuler',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;
            $.ajax({
                url: '{{ url('finances/frais-ecoles/synchroniser') }}/' + cible, method: 'POST',
                data: { _token: '{{ csrf_token() }}' }, headers: { 'Accept': 'application/json' },
            }).done(function (res) {
                const b = res.data?.bilan || {};
                const incoherences = (b.incoherences || []).map(i => '<li>' + texte(i) + '</li>').join('');
                $('#sync-titre').html('<i class="fas fa-sync-alt me-2"></i>Synchronisation — ' + texte(b.cible || libelle));
                $('#sync-contenu').html(
                    '<div class="indicateurs mb-3">'
                    + '<div class="indicateur"><span>Créés</span><strong id="sync-crees">' + (b.crees ?? 0) + '</strong></div>'
                    + '<div class="indicateur"><span>Mis à jour</span><strong id="sync-maj">' + (b.mis_a_jour ?? 0) + '</strong></div>'
                    + '<div class="indicateur"><span>Déjà à jour</span><strong id="sync-ignores">' + (b.ignores ?? 0) + '</strong></div>'
                    + '<div class="indicateur"><span>À vérifier</span><strong id="sync-incoherences" class="text-danger">' + (b.incoherences || []).length + '</strong></div>'
                    + '</div>'
                    + (incoherences ? '<div class="small fw-semibold text-danger">Points à vérifier :</div><ul class="small mb-0">' + incoherences + '</ul>' : '')
                );
                bootstrap.Modal.getOrCreateInstance(document.getElementById('syncModal')).show();
            }).fail(xhr => window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error'));
        });
    });
});
</script>
@endpush
