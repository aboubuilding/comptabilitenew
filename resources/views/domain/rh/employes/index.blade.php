@extends('admin.layouts.app')

@section('title', 'Employés')
@section('page_icon', 'fa-users')
@section('page_title', 'Employés')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item active">Employés</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('rh.employes.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('rh.professions.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-briefcase me-1"></i> Professions
    </x-bouton>
    @can('employes.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelEmploye" data-bs-toggle="modal" data-bs-target="#employeModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvel employé
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .emp-page { --e-bg-soft:var(--eim-cream); --e-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--e-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--e-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--e-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--e-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--e-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--e-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--e-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-employes thead tr { background:var(--e-bg-soft); }
    .table-employes thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--e-border); white-space:nowrap; }
    .table-employes thead th i { color: var(--school-gold-dark); }
    .table-employes tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--e-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-employes tbody tr:hover { background:var(--eim-hover); }

    .employe-chip { display:flex; align-items:center; gap:10px; }
    .employe-avatar {
        width:38px; height:38px; border-radius:50%;
        background:linear-gradient(145deg,var(--school-gold-soft),var(--school-gold));
        color:var(--school-red-deep); font-size:.85rem; font-weight:800;
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .employe-nom { font-weight:700; color:var(--school-ink); }
    .employe-sub { font-size:.72rem; color:var(--school-muted); }

    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }


    .badge-profession { display:inline-flex; padding:.3em .8em; border-radius:999px; background:var(--e-bg-soft); color:var(--school-red); font-size:.72rem; font-weight:600; }
    .badge-paiement.banque   { background:#e0ecfc; color:#2563eb; }
    .badge-paiement.billette { background:#fdf4e0; color:var(--school-gold-dark); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-rh { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-rh { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--e-bg-soft); border:1px solid var(--e-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; display:flex; align-items:center; gap:6px; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--e-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--e-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="emp-page">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-users" couleur="blue" libelle="Employés">{{ $stats['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-user-check" couleur="green" libelle="Actifs">{{ $stats['actifs'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-shield-alt" couleur="gold" libelle="Assurés CNSS">{{ $stats['assures_cnss'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="red" libelle="Masse salariale">{{ number_format($stats['masse_salariale'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    {{-- Tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <x-recherche name="search" placeholder="Nom, CNSS, téléphone..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="profession_id" class="form-select filtre-input">
                        <option value="">— Profession —</option>
                        @foreach ($professions as $p)
                            <option value="{{ $p->id }}" @selected(($filters['profession_id'] ?? '') == $p->id)>
                                {{ $p->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        <option value="1" @selected(($filters['statut'] ?? '') == 1)>Actif</option>
                        <option value="0" @selected(($filters['statut'] ?? '') === '0')>Inactif</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('rh.employes.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-employes align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-user me-2"></i>Employé</th>
                            <th><i class="fas fa-briefcase me-2"></i>Profession</th>
                            <th><i class="fas fa-id-card me-2"></i>N° CNSS</th>
                            <th><i class="fas fa-phone me-2"></i>Téléphone</th>
                            <th><i class="fas fa-wallet me-2"></i>Paiement</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Salaire</th>
                            <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($employes as $employe)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('rh.employes.show', $employe) }}" class="text-decoration-none">
                                    <div class="employe-chip">
                                        <div class="employe-avatar">
                                            {{ strtoupper(substr($employe->nom_prenom ?? '?', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="employe-nom">{{ $employe->nom_prenom }}</div>
                                            <div class="employe-sub">
                                                {{ $employe->ville ?? '—' }}
                                                @if ($employe->date_embauche)
                                                    · depuis {{ $employe->date_embauche->format('Y') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                @if ($employe->profession)
                                    <span class="badge-profession">{{ $employe->profession->libelle }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-sm" style="font-family:monospace;">{{ $employe->numero_cnss ?? '—' }}</td>
                            <td class="text-sm">
                                @if ($employe->telephone)
                                    <i class="fas fa-phone me-1 text-muted"></i>{{ $employe->telephone }}
                                @endif
                            </td>
                            <td>
                                @if ($employe->isPayeParBanque())
                                    <x-badge class="badge-paiement banque">
                                        <i class="fas fa-university me-1"></i>Banque
                                    </x-badge>
                                @else
                                    <x-badge class="badge-paiement billette">
                                        <i class="fas fa-money-bill-wave me-1"></i>Billetterie
                                    </x-badge>
                                @endif
                            </td>
                            <td class="num grand">{{ number_format((float) $employe->salaire, 0, ',', ' ') }}</td>
                            <td>
                                <x-badge class="{{ $employe->isActif() ? 'actif' : 'inactif' }}" point>{{ $employe->isActif() ? 'Actif' : 'Inactif' }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('rh.employes.show', $employe) }}">
                                                <i class="fas fa-eye"></i> Voir la fiche
                                            </a>
                                        </li>
                                        @can('employes.gerer')
                                            <li>
                                                <button class="dropdown-item btn-edit-employe"
                                                        data-id="{{ $employe->id }}"
                                                        data-nom="{{ $employe->nom_prenom }}"
                                                        data-cnss="{{ $employe->numero_cnss }}"
                                                        data-quartier="{{ $employe->quartier }}"
                                                        data-ville="{{ $employe->ville }}"
                                                        data-telephone="{{ $employe->telephone }}"
                                                        data-email="{{ $employe->email }}"
                                                        data-genre="{{ $employe->genre }}"
                                                        data-situation="{{ $employe->situation_familiale }}"
                                                        data-date-embauche="{{ optional($employe->date_embauche)->toDateString() }}"
                                                        data-date-naissance="{{ optional($employe->date_naissance)->toDateString() }}"
                                                        data-lieu-naissance="{{ $employe->lieu_naissance }}"
                                                        data-date-fin-contrat="{{ optional($employe->date_fin_contrat)->toDateString() }}"
                                                        data-is-cnss="{{ $employe->is_cnss }}"
                                                        data-is-amu="{{ $employe->is_amu }}"
                                                        data-nbre-enfant="{{ $employe->nbre_enfant }}"
                                                        data-is-pension="{{ $employe->is_pension_validite }}"
                                                        data-taux-pension="{{ $employe->taux_pension_validite }}"
                                                        data-nature-pension="{{ $employe->nature_pension_validite }}"
                                                        data-salaire="{{ $employe->salaire }}"
                                                        data-profession="{{ $employe->profession_id }}"
                                                        {{-- Nouveaux champs : mode de paiement + IRPP --}}
                                                        data-mode-paiement="{{ $employe->mode_paiement ?? 'billette' }}"
                                                        data-banque="{{ $employe->banque }}"
                                                        data-iban="{{ $employe->iban }}"
                                                        data-titulaire="{{ $employe->titulaire_compte }}"
                                                        data-irpp-mode="{{ $employe->irpp_mode ?? 'auto' }}"
                                                        data-bs-toggle="modal" data-bs-target="#employeModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-actions.supprimer :action="route('rh.employes.destroy', $employe)" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="8" icone="fas fa-users" :paragraphe="false"><p class="mb-0">Aucun employé enregistré.</p></x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$employes->appends(request()->query())" /></div>

    {{-- Modale employé --}}
    @can('employes.gerer')
    <x-modale id="employeModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-rh" entete-class="modal-header-rh" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="employe-form">@csrf
                    <input type="hidden" name="_method" id="emp-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="emp-title"><i class="fas fa-user-plus me-2"></i>Nouvel employé</x-slot:titre>
        {{-- Identité --}}
        <x-section titre="Identité" icone="fas fa-user" class="mb-3">
            <div class="row g-3">
                <div class="col-md-8">
                    <x-libelle requis>Nom & prénom</x-libelle>
                    <input type="text" name="nom_prenom" id="em-nom" class="form-control" maxlength="150" required>
                    <div class="text-danger text-xs mt-1" id="nom_prenom-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle>Genre</x-libelle>
                    <select name="genre" id="em-genre" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach ($genres as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Date de naissance</x-libelle>
                    <input type="date" name="date_naissance" id="em-date-naissance" class="form-control">
                </div>
                <div class="col-md-6">
                    <x-libelle>Lieu de naissance</x-libelle>
                    <input type="text" name="lieu_naissance" id="em-lieu-naissance" class="form-control" maxlength="150">
                </div>
            </div>
        </x-section>

        {{-- Contact --}}
        <x-section class="mb-3">
            <div class="form-section-title"><i class="fas fa-address-book"></i> Contact & adresse</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle>Téléphone</x-libelle>
                    <input type="text" name="telephone" id="em-telephone" class="form-control" maxlength="30">
                </div>
                <div class="col-md-6">
                    <x-libelle>Email</x-libelle>
                    <input type="email" name="email" id="em-email" class="form-control" maxlength="150">
                </div>
                <div class="col-md-6">
                    <x-libelle>Quartier</x-libelle>
                    <input type="text" name="quartier" id="em-quartier" class="form-control" maxlength="100">
                </div>
                <div class="col-md-6">
                    <x-libelle>Ville</x-libelle>
                    <input type="text" name="ville" id="em-ville" class="form-control" maxlength="100">
                </div>
            </div>
        </x-section>

        {{-- Contrat & salaire --}}
        <x-section class="mb-3">
            <div class="form-section-title"><i class="fas fa-briefcase"></i> Contrat & salaire</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle>Profession</x-libelle>
                    <select name="profession_id" id="em-profession" class="form-select">
                        <option value="">— Aucune —</option>
                        @foreach ($professions as $p)
                            <option value="{{ $p->id }}">{{ $p->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Salaire</x-libelle>
                    <input type="number" name="salaire" id="em-salaire" step="0.01" min="0" class="form-control" required>
                    <div class="text-danger text-xs mt-1" id="salaire-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle>Date d'embauche</x-libelle>
                    <input type="date" name="date_embauche" id="em-date-embauche" class="form-control">
                </div>
                <div class="col-md-6">
                    <x-libelle>Date de fin de contrat</x-libelle>
                    <input type="date" name="date_fin_contrat" id="em-date-fin-contrat" class="form-control">
                </div>
                <div class="col-md-6">
                    <x-libelle>N° CNSS</x-libelle>
                    <input type="text" name="numero_cnss" id="em-cnss" class="form-control" maxlength="50">
                </div>
            </div>
        </x-section>

        {{-- Mode de paiement & IRPP --}}
        <x-section class="mb-3">
            <div class="form-section-title"><i class="fas fa-wallet"></i> Mode de paiement & fiscalité</div>
            <div class="row g-3">
                {{-- Mode de paiement --}}
                <div class="col-md-4">
                    <x-libelle requis>Mode de paiement</x-libelle>
                    <select name="mode_paiement" id="em-mode-paiement" class="form-select" required>
                        <option value="billette">Billetterie (espèces)</option>
                        <option value="banque">Banque (virement)</option>
                    </select>
                </div>

                {{-- Mode IRPP --}}
                <div class="col-md-4">
                    <x-libelle requis>Mode IRPP</x-libelle>
                    <select name="irpp_mode" id="em-irpp-mode" class="form-select" required>
                        <option value="auto">Automatique (barème)</option>
                        <option value="manuel">Manuel (montant fixe)</option>
                    </select>
                    <div class="form-text small">Automatique = barème progressif sur le net imposable.</div>
                </div>

                {{-- Bloc banque : affiché uniquement si mode = banque --}}
                <div class="col-md-4 mode-banque-bloc" style="display:none;">
                    <x-libelle>Banque</x-libelle>
                    <input type="text" name="banque" id="em-banque" class="form-control" maxlength="100">
                </div>
                <div class="col-md-6 mode-banque-bloc" style="display:none;">
                    <x-libelle>IBAN / RIB</x-libelle>
                    <input type="text" name="iban" id="em-iban" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6 mode-banque-bloc" style="display:none;">
                    <x-libelle>Titulaire du compte</x-libelle>
                    <input type="text" name="titulaire_compte" id="em-titulaire" class="form-control" maxlength="150">
                </div>
            </div>
        </x-section>

        {{-- Protections sociales --}}
        <x-section titre="Protections sociales" icone="fas fa-shield-alt">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_cnss" value="1" id="em-is-cnss">
                        <label class="form-check-label small" for="em-is-cnss">Assuré CNSS</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_amu" value="1" id="em-is-amu">
                        <label class="form-check-label small" for="em-is-amu">Assuré AMU</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <x-libelle>Nombre d'enfants</x-libelle>
                    <input type="number" name="nbre_enfant" id="em-nbre-enfant" min="0" max="30" class="form-control">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_pension_validite" value="1" id="em-is-pension">
                        <label class="form-check-label small" for="em-is-pension">Pension validité</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <x-libelle>Taux pension (%)</x-libelle>
                    <input type="number" name="taux_pension_validite" id="em-taux-pension" min="0" max="100" class="form-control">
                </div>
                <div class="col-md-4">
                    <x-libelle>Nature pension</x-libelle>
                    <input type="number" name="nature_pension_validite" id="em-nature-pension" step="0.01" min="0" class="form-control">
                </div>
                <div class="col-md-4">
                    <x-libelle>Situation familiale</x-libelle>
                    <select name="situation_familiale" id="em-situation" class="form-select">
                        <option value="">— Aucune —</option>
                        @foreach ($situations as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
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
    const $form = $('#employe-form');
    if (!$form.length) return;
    let currentId = null;

    // ================================================================
    // TOGGLE : affiche/cache le bloc "banque" selon le mode de paiement
    // ================================================================
    function majBlocBanque() {
        const mode = $('#em-mode-paiement').val();
        if (mode === 'banque') {
            $('.mode-banque-bloc').show();
        } else {
            $('.mode-banque-bloc').hide();
            // On vide les champs banque si on repasse en billetterie
            $('#em-banque, #em-iban, #em-titulaire').val('');
        }
    }

    $('#em-mode-paiement').on('change', majBlocBanque);

    // ================================================================
    // RÉINITIALISATION COMPLÈTE (nouvel employé)
    // ================================================================
    function resetForm() {
        $form[0].reset();
        // Vider les champs cachés manuellement (reset ne suffit pas toujours)
        $('#em-banque, #em-iban, #em-titulaire').val('');
        // Cocher/décocher explicitement
        $('#em-is-cnss, #em-is-amu, #em-is-pension').prop('checked', false);
        // Valeurs par défaut
        $('#em-mode-paiement').val('billette');
        $('#em-irpp-mode').val('auto');
        majBlocBanque();
    }

    // ================================================================
    // BOUTON NOUVEL EMPLOYÉ
    // ================================================================
    $('#btnNouvelEmploye').on('click', function () {
        currentId = null;
        resetForm();
        $('#emp-method').val('POST');
        $('#emp-title').html('<i class="fas fa-user-plus me-2"></i>Nouvel employé');
        // Vider les erreurs
        $form.find('[id$="-error"]').text('');
    });

    // ================================================================
    // BOUTON MODIFIER (pré-remplissage complet)
    // ================================================================
    $(document).on('click', '.btn-edit-employe', function () {
        const $btn = $(this);
        currentId = $btn.data('id');

        // Identité
        $('#em-nom').val($btn.data('nom'));
        $('#em-genre').val($btn.data('genre') ?? '');
        $('#em-date-naissance').val($btn.data('date-naissance') ?? '');
        $('#em-lieu-naissance').val($btn.data('lieu-naissance') ?? '');

        // Contact
        $('#em-telephone').val($btn.data('telephone') ?? '');
        $('#em-email').val($btn.data('email') ?? '');
        $('#em-quartier').val($btn.data('quartier') ?? '');
        $('#em-ville').val($btn.data('ville') ?? '');

        // Contrat
        $('#em-profession').val($btn.data('profession') ?? '');
        $('#em-salaire').val($btn.data('salaire') ?? '');
        $('#em-date-embauche').val($btn.data('date-embauche') ?? '');
        $('#em-date-fin-contrat').val($btn.data('date-fin-contrat') ?? '');
        $('#em-cnss').val($btn.data('cnss') ?? '');

        // Mode paiement + IRPP + coordonnées bancaires
        $('#em-mode-paiement').val($btn.data('mode-paiement') ?? 'billette');
        $('#em-irpp-mode').val($btn.data('irpp-mode') ?? 'auto');
        $('#em-banque').val($btn.data('banque') ?? '');
        $('#em-iban').val($btn.data('iban') ?? '');
        $('#em-titulaire').val($btn.data('titulaire') ?? '');

        // Protections sociales
        $('#em-is-cnss').prop('checked', $btn.data('is-cnss') == 1);
        $('#em-is-amu').prop('checked', $btn.data('is-amu') == 1);
        $('#em-nbre-enfant').val($btn.data('nbre-enfant') ?? 0);
        $('#em-is-pension').prop('checked', $btn.data('is-pension') == 1);
        $('#em-taux-pension').val($btn.data('taux-pension') ?? '');
        $('#em-nature-pension').val($btn.data('nature-pension') ?? '');
        $('#em-situation').val($btn.data('situation') ?? '');

        // Titre + méthode
        $('#emp-method').val('PUT');
        $('#emp-title').html('<i class="fas fa-pen me-2"></i>Modifier l\'employé');

        // Vider les erreurs
        $form.find('[id$="-error"]').text('');

        // Affiche ou cache le bloc banque en fonction du mode pré-rempli
        majBlocBanque();
    });

    // ================================================================
    // SOUMISSION
    // ================================================================
    $form.on('submit', function (e) {
        e.preventDefault();

        // Vider les erreurs avant envoi
        $form.find('[id$="-error"]').text('');

        const url = currentId
            ? `{{ url('rh/employes') }}/${currentId}`
            : `{{ route('rh.employes.store') }}`;

        $.ajax({
            url,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#employeModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => {
                        $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]);
                    });
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    // ================================================================
    // INITIALISATION
    // ================================================================
    // Au cas où la modale serait ouverte avec un mode déjà sélectionné
    majBlocBanque();
});
</script>
@endpush