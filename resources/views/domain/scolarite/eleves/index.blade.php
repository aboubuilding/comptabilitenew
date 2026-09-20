{{-- resources/views/admin/scolarite/eleves/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Élèves')
@section('page_icon', 'fa-user-graduate')
@section('page_title', 'Élèves')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Scolarité</li>
    <li class="breadcrumb-item active">Élèves</li>
@endsection

@section('page_actions')
    <a href="{{ route('scolarite.eleves.create') }}" class="btn btn-nouveau">
        <i class="fas fa-user-plus me-1"></i> Nouvelle fiche élève
    </a>
@endsection

@section('css')
<style>
    /* Jetons propres à cette page — school-red, gold, ink, muted, ff...
       viennent déjà de layouts/app.blade.php. */
    .eleves-page {
        --eleves-bg-soft: #faf6ee;
        --eleves-border: #ece4d6;
    }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--eleves-border) !important; }

    .filtre-label { font-size: .76rem; font-weight: 600; color: #5b6b7a; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 5px; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--eleves-border); padding: .5rem .8rem; font-size: .85rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #9aa7b2; font-size: .8rem; }
    .search-input { padding-left: 32px !important; }

    .btn-filtrer { background: var(--school-red); color: #fff; border: none; border-radius: 10px; padding: .5rem 1rem; font-weight: 600; font-size: .85rem; }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; }

    .compteur-badge {
        display: inline-block; background: var(--eleves-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .82rem; padding: .4rem .85rem; border-radius: 999px;
        border: 1px solid var(--eleves-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(200,30,58,.25); text-decoration: none;
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }

    .table-eleves thead tr { background: var(--eleves-bg-soft); }
    .table-eleves thead th {
        color: var(--school-red); font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--eleves-border); padding: .8rem .9rem; white-space: nowrap;
    }
    .table-eleves tbody td { padding: .7rem .9rem; border-bottom: 1px solid var(--eleves-border); font-size: .86rem; vertical-align: middle; color: var(--school-ink); }
    .table-eleves tbody tr:hover { background: #fdfaf5; }
    .table-eleves tbody tr:last-child td { border-bottom: none; }

    .eleve-photo {
        width: 38px; height: 38px; border-radius: 50%; object-fit: cover;
        border: 1px solid var(--eleves-border);
    }
    .eleve-photo-placeholder {
        width: 38px; height: 38px; border-radius: 50%; background: var(--eleves-bg-soft);
        color: var(--school-red); display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .78rem; border: 1px solid var(--eleves-border);
    }

    .eleve-nom { font-weight: 700; font-size: .88rem; display: block; }
    .eleve-matricule { font-family: monospace; font-size: .8rem; color: var(--school-muted); }

    .montant-du { font-weight: 700; color: var(--school-red-dark); }
    .montant-encaisse { color: #1c7a4d; font-weight: 600; }
    .montant-zero { color: var(--school-muted); }

    .badge-etat { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; padding: .35em .75em; border-radius: 999px; }
    .badge-etat-actif { background: #e3f7ec; color: #1c7a4d; }
    .badge-etat-abandon { background: #fbeaea; color: #b13b3b; }
    .badge-etat-non-inscrit { background: var(--eleves-bg-soft); color: var(--school-muted); }

    .empty-state { padding: 3rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }

    /* .btn-action / .dropdown-menu-actions : définies globalement dans
       layouts/app.blade.php. */
</style>
@endsection

@section('contenu')
    <div class="eleves-page">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                {{-- Filtres GET : année, cycle, niveau, classe, statut, sexe,
                     recherche (cahier des charges §4, 1.1). L'année par
                     défaut est celle du contexte actif (sélecteur du
                     header), mais reste modifiable ici sans changer ce
                     contexte global. --}}
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Année</label>
                        <select name="annee_id" class="form-select filtre-input" onchange="this.form.submit()">
                            @foreach ($anneesOptions as $annee)
                                <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->libelle }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Cycle</label>
                        <select name="cycle_id" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach ($cyclesOptions as $cycle)
                                <option value="{{ $cycle->id }}" {{ request('cycle_id') == $cycle->id ? 'selected' : '' }}>
                                    {{ $cycle->libelle }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Niveau</label>
                        <select name="niveau_id" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach ($niveauxOptions as $niveau)
                                <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->libelle }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Classe</label>
                        <select name="classe_id" class="form-select filtre-input">
                            <option value="">Toutes</option>
                            @foreach ($classesOptions as $classe)
                                <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                    {{ $classe->libelle }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Statut</label>
                        <select name="statut" class="form-select filtre-input">
                            <option value="">Tous</option>
                            <option value="0" {{ request('statut') === '0' ? 'selected' : '' }}>Actif</option>
                            <option value="1" {{ request('statut') === '1' ? 'selected' : '' }}>Abandonné</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Sexe</label>
                        <select name="sexe" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach (\App\Domain\Scolarite\Types\Sexe::options() as $val => $label)
                                <option value="{{ $val }}" {{ request('sexe') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="filtre-label">Recherche</label>
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control filtre-input search-input"
                                   placeholder="Nom, prénom ou matricule..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
                    </div>
                    <div class="col-6 col-md-2 text-end">
                        <span class="compteur-badge">{{ $eleves->total() }} élève(s)</span>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-eleves align-middle mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Matricule</th>
                                <th>Nom &amp; prénom</th>
                                <th>Classe</th>
                                <th>Sexe</th>
                                <th>Naissance</th>
                                <th>Nationalité</th>
                                <th>Statut</th>
                                <th class="text-end">Montant dû</th>
                                <th class="text-end">Encaissé</th>
                                <th class="text-center" style="width:70px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($eleves as $eleve)
                                @php
                                    $inscription = $eleve->inscriptionPourAnnee;
                                    $montantDu = $inscription ? max(0, $inscription->montant_engage - ($eleve->montant_encaisse ?? 0)) : null;
                                @endphp
                                <tr>
                                    <td>
                                        @if ($eleve->photo)
                                            <img src="{{ asset('storage/' . $eleve->photo) }}" alt="" class="eleve-photo">
                                        @else
                                            <div class="eleve-photo-placeholder">{{ strtoupper(substr($eleve->prenom ?? '?', 0, 1) . substr($eleve->nom ?? '?', 0, 1)) }}</div>
                                        @endif
                                    </td>
                                    <td><span class="eleve-matricule">{{ $eleve->matricule ?? '—' }}</span></td>
                                    <td>
                                        <span class="eleve-nom">{{ $eleve->nom }} {{ $eleve->prenom }}</span>
                                    </td>
                                    <td>{{ $inscription?->classe?->libelle ?? '—' }}</td>
                                    <td>{{ $eleve->sexe?->label() ?? '—' }}</td>
                                    <td>{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $eleve->nationalite?->libelle ?? '—' }}</td>
                                    <td>
                                        @if (!$inscription)
                                            <span class="badge-etat badge-etat-non-inscrit"><i class="fas fa-question-circle"></i>Non inscrit</span>
                                        @elseif ($inscription->isAbandonnee())
                                            <span class="badge-etat badge-etat-abandon"><i class="fas fa-times-circle"></i>Abandonné</span>
                                        @else
                                            <span class="badge-etat badge-etat-actif"><i class="fas fa-check-circle"></i>Actif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (is_null($montantDu))
                                            <span class="montant-zero">—</span>
                                        @elseif ($montantDu > 0)
                                            <span class="montant-du">{{ number_format($montantDu, 0, ',', ' ') }} F</span>
                                        @else
                                            <span class="montant-zero">0 F</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="montant-encaisse">{{ number_format($eleve->montant_encaisse ?? 0, 0, ',', ' ') }} F</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                                <li>
                                                    <a href="{{ route('scolarite.eleves.edit', $eleve) }}" class="dropdown-item">
                                                        <i class="fas fa-pen"></i> Modifier / consulter
                                                    </a>
                                                </li>
                                                <li>
                                                    {{-- TODO : détail financier, prochain tour --}}
                                                    <a href="#" class="dropdown-item">
                                                        <i class="fas fa-wallet"></i> Situation financière
                                                    </a>
                                                </li>
                                                <li>
                                                    {{-- TODO : fiche imprimable, prochain tour --}}
                                                    <a href="#" class="dropdown-item">
                                                        <i class="fas fa-print"></i> Imprimer la fiche
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('scolarite.eleves.archiver', $eleve) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Archiver cette fiche élève ?"
                                                          data-confirm-text="Elle n'apparaîtra plus dans les listes actives.">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-box-archive"></i> Archiver
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11">
                                        <div class="empty-state">
                                            <i class="fas fa-user-graduate"></i>
                                            <p class="mb-0">Aucun élève ne correspond à ces critères.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">
            {{ $eleves->appends(request()->query())->links() }}
        </div>
    </div>
@endsection