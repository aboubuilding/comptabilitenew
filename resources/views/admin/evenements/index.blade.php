{{-- resources/views/admin/evenements/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Événements')

@section('page_title')
    <i class="fas fa-calendar-alt"></i> Événements
    <span class="badge bg-info ms-2">{{ $anneeCourante->libelle ?? 'Année en cours' }}</span>
@endsection

@section('page_actions')
    <div class="page-actions-wrapper">
        <button type="button" class="btn-btp btn-nouveau" data-bs-toggle="modal" data-bs-target="#evenementModal" id="btnNouveau">
            <i class="fas fa-plus-circle"></i> Nouvel événement
        </button>
    </div>
@endsection

@section('contenu')
    <div class="row">
        <div class="col-12">
            <div class="card-btp shadow-sm evenement-card">
                <div class="card-header-btp bg-white border-0 py-3 px-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label filtre-label"><i class="fas fa-tag"></i> Type</label>
                            <select id="filterType" class="form-select filtre-input">
                                <option value="">Tous</option>
                                <option value="excursion">Excursion</option>
                                <option value="voyage">Voyage</option>
                                <option value="sortie_pedagogique">Sortie Pédagogique</option>
                                <option value="competition">Compétition</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label filtre-label"><i class="fas fa-clock"></i> Statut</label>
                            <select id="filterStatut" class="form-select filtre-input">
                                <option value="">Tous</option>
                                <option value="upcoming">À venir</option>
                                <option value="past">Passés</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label filtre-label"><i class="fas fa-toggle-on"></i> État</label>
                            <select id="filterActive" class="form-select filtre-input">
                                <option value="">Tous</option>
                                <option value="1">Actif</option>
                                <option value="0">Inactif</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-end">
                            <span class="compteur-badge" id="evenementCount">0 événement(s)</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-btp table-hover align-middle mb-0" id="evenementsTable">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th><i class="fas fa-tag"></i> Nom</th>
                                <th><i class="fas fa-list"></i> Type</th>
                                <th><i class="fas fa-calendar-day"></i> Date</th>
                                <th><i class="fas fa-users"></i> Capacité</th>
                                <th><i class="fas fa-money-bill"></i> Participation</th>
                                <th><i class="fas fa-clock"></i> Statut</th>
                                <th><i class="fas fa-power-off"></i> État</th>
                                <th class="text-center" style="width:100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout / Modification -->
    <div class="modal fade" id="evenementModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-btp">
                <div class="modal-header modal-header-btp">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Nouvel événement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="evenementForm">
                        <input type="hidden" id="evenementId">
                        <input type="hidden" id="annee_id" value="{{ $anneeCourante->id }}">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations</div>
                                    <div class="mb-3">
                                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                                        <input type="text" id="nom" class="form-control" placeholder="Nom de l'événement" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Type <span class="text-danger">*</span></label>
                                        <select id="type" class="form-control" required>
                                            <option value="">Sélectionner</option>
                                            <option value="excursion">Excursion</option>
                                            <option value="voyage">Voyage</option>
                                            <option value="sortie_pedagogique">Sortie Pédagogique</option>
                                            <option value="competition">Compétition</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea id="description" class="form-control" rows="3" placeholder="Description de l'événement"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-cog"></i> Configuration</div>
                                    <div class="mb-3">
                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" id="date_evenement" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Participation (FCFA) <span class="text-danger">*</span></label>
                                        <input type="number" id="participation" class="form-control" placeholder="0" step="0.01" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Capacité</label>
                                        <input type="number" id="capacite" class="form-control" placeholder="Nombre de places" min="1">
                                        <small class="text-muted">Laissez vide pour illimité</small>
                                    </div>
                                    <div class="mb-0">
                                        <label class="switch-check">
                                            <input type="checkbox" id="est_active" value="1" checked>
                                            <span class="switch-slider"></span>
                                            <span class="switch-label">Actif</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-enregistrer" id="saveEvenementBtn"><i class="fas fa-save me-1"></i> Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détail -->
    <div class="modal fade" id="detailModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-btp">
                <div class="modal-header modal-header-btp">
                    <h5 class="modal-title" id="detailModalTitle"><i class="fas fa-circle-info me-2"></i>Détail de l'événement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="detailModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast-btp" id="toastBtp"></div>
@endsection

@push('css')
    <!-- CSS d'intégration Bootstrap 5 pour DataTables — manquait, seul le JS
         (dataTables.bootstrap5.min.js) était chargé plus bas. Sans ce fichier,
         la pagination, la recherche et le sélecteur de longueur perdent tout
         leur style Bootstrap (bordures, espacement, alignement). -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <style>
        /* Palette alignée sur l'identité rouge/or de l'EIM (cf. login,
           header, frais-ecoles) — ces variables n'étaient définies nulle
           part ici, ce qui rendait tous les boutons blanc sur blanc. */
        :root {
            --btp-primary       : #7a0f1c;
            --btp-primary-light : #b31c2b;
            --btp-accent        : #d4a94d;
            --btp-accent-dark   : #b8860b;
            --btp-success       : #1c7a4d;
            --btp-warning       : #b8720b;
            --btp-danger        : #c81e3a;
            --btp-border        : #ece4d6;
            --btp-bg-soft       : #faf6ee;
            --btp-text          : #1f2d3a;
            --btp-text-muted    : #6f7e8c;
        }

        .page-actions-wrapper {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
        }

        .evenement-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }

        .card-header-btp {
            border-bottom: 1px solid var(--btp-border) !important;
        }

        .filtre-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--btp-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 4px;
        }

        .filtre-label i { color: var(--btp-primary); margin-right: 4px; }

        .filtre-input {
            border-radius: 8px;
            border: 1px solid var(--btp-border);
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .filtre-input:focus {
            border-color: var(--btp-primary-light);
            box-shadow: 0 0 0 0.2rem rgba(122, 15, 28, 0.12);
        }

        .compteur-badge {
            display: inline-block;
            background: var(--btp-bg-soft);
            color: var(--btp-primary);
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.45rem 0.9rem;
            border-radius: 999px;
            border: 1px solid var(--btp-border);
        }

        .btn-nouveau {
            background: linear-gradient(135deg, var(--btp-primary) 0%, var(--btp-primary-light) 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(122, 15, 28, 0.25);
            transition: all 0.2s ease;
        }

        .btn-nouveau:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(122, 15, 28, 0.35);
            color: #fff;
        }

        .table-btp thead tr {
            background: var(--btp-bg-soft);
        }

        .table-btp thead th {
            color: var(--btp-primary);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 2px solid var(--btp-border);
            padding: 0.7rem 0.8rem;
            white-space: nowrap;
        }

        .table-btp thead th i { margin-right: 4px; color: var(--btp-accent-dark); }

        .table-btp tbody td {
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid var(--btp-border);
            color: var(--btp-text);
            font-size: 0.85rem;
            vertical-align: middle;
        }

        .table-btp tbody tr:hover {
            background: #fdfaf5;
        }

        .table-btp tbody tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.3rem 0.6rem;
            border-radius: 999px;
        }

        .badge-success { background-color: #e3f7ec; color: #1c7a4d; }
        .badge-danger { background-color: #fbeaea; color: #b13b3b; }
        .badge-info { background-color: #fdecee; color: #7a0f1c; }
        .badge-warning { background-color: #fdf1dc; color: #97650f; }
        .badge-primary { background-color: #f3e6cf; color: #8a6410; }
        .badge-secondary { background-color: #e9ecef; color: #5b6b7a; }

        .action-dropdown-btn {
            border-radius: 6px;
            border: 1px solid var(--btp-border);
            background: #fff;
            color: var(--btp-text-muted);
            width: 30px;
            height: 30px;
            transition: all 0.15s ease;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .action-dropdown-btn:hover {
            background: var(--btp-bg-soft);
            border-color: var(--btp-primary-light);
            color: var(--btp-primary);
        }

        .dropdown-menu {
            border-radius: 10px;
            border: 1px solid var(--btp-border);
            box-shadow: 0 8px 24px rgba(122, 15, 28, 0.10);
            padding: 6px;
            min-width: 180px;
        }

        .dropdown-item {
            border-radius: 6px;
            font-size: 0.85rem;
            padding: 0.4rem 0.75rem;
        }

        .dropdown-item i {
            width: 18px;
            margin-right: 6px;
            color: var(--btp-text-muted);
        }

        .dropdown-item:hover {
            background: var(--btp-bg-soft);
        }

        .dropdown-item.text-danger i {
            color: var(--btp-danger);
        }

        .modal-btp { border: none; border-radius: 16px; overflow: hidden; }

        .modal-header-btp {
            background: linear-gradient(135deg, var(--btp-primary) 0%, var(--btp-primary-light) 100%);
            color: #fff;
            border-bottom: none;
            padding: 1rem 1.5rem;
        }

        .modal-header-btp .modal-title { font-weight: 600; font-size: 1rem; }

        .form-section {
            background: var(--btp-bg-soft);
            border: 1px solid var(--btp-border);
            border-radius: 12px;
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
        }

        .form-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--btp-primary);
            margin-bottom: 0.75rem;
        }

        .form-section-title i { margin-right: 6px; color: var(--btp-accent-dark); }

        .form-section .form-control {
            border-radius: 8px;
            border: 1px solid var(--btp-border);
            font-size: 0.9rem;
            padding: 0.5rem 0.8rem;
        }

        .form-section .form-control:focus {
            border-color: var(--btp-primary-light);
            box-shadow: 0 0 0 0.2rem rgba(122, 15, 28, 0.10);
        }

        .switch-check {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            background: #fff;
            border: 1px solid var(--btp-border);
            border-radius: 10px;
            padding: 0.5rem 0.8rem;
            margin: 0;
        }

        .switch-check input { display: none; }

        .switch-slider {
            width: 38px;
            height: 20px;
            border-radius: 999px;
            background: #d7dde2;
            position: relative;
            flex-shrink: 0;
            transition: background 0.2s ease;
        }

        .switch-slider::before {
            content: "";
            position: absolute;
            top: 2px; left: 2px;
            width: 16px; height: 16px;
            border-radius: 50%;
            background: #fff;
            transition: transform 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* "Actif" reste vert, plus intuitif que l'accent doré (réservé aux
           éléments de marque : toast, icônes de titre...). */
        .switch-check input:checked + .switch-slider { background: var(--btp-success); }
        .switch-check input:checked + .switch-slider::before { transform: translateX(18px); }
        .switch-label { font-size: 0.85rem; font-weight: 500; color: var(--btp-text); }

        .modal-footer {
            border-top: 1px solid var(--btp-border);
            padding: 0.8rem 1.5rem;
        }

        .btn-annuler {
            background: #fff;
            border: 1px solid var(--btp-border);
            color: var(--btp-text-muted);
            border-radius: 8px;
            font-weight: 500;
            padding: 0.4rem 1.1rem;
            font-size: 0.85rem;
        }

        .btn-annuler:hover { background: var(--btp-bg-soft); color: var(--btp-text); }

        .btn-enregistrer {
            background: linear-gradient(135deg, var(--btp-primary) 0%, var(--btp-primary-light) 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.4rem 1.3rem;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-enregistrer:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(122, 15, 28, 0.3); color: #fff; }

        .toast-btp {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #fff;
            color: var(--btp-text);
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.18);
            opacity: 0;
            transform: translateY(-16px) scale(0.98);
            transition: all 0.3s cubic-bezier(.4,0,.2,1);
            z-index: 9999;
            border-left: 4px solid var(--btp-accent);
            font-weight: 500;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 360px;
        }

        .toast-btp.show { opacity: 1; transform: translateY(0) scale(1); }
        .toast-btp.toast-danger { border-left-color: var(--btp-danger); }
        .toast-btp.toast-warning { border-left-color: var(--btp-warning); }
        .toast-btp span { font-size: 0.9rem; }

        /* DataTables */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            padding: 0.5rem 0.8rem;
        }

        .dataTables_wrapper .dataTables_info {
            padding: 0.5rem 0.8rem;
            font-size: 0.85rem;
            color: var(--btp-text-muted);
        }

        .dataTables_wrapper .dataTables_paginate {
            padding: 0.5rem 0.8rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.3rem 0.7rem;
            font-size: 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--btp-border);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--btp-primary) !important;
            color: #fff !important;
            border-color: var(--btp-primary) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--btp-primary-light) !important;
            color: #fff !important;
            border-color: var(--btp-primary-light) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            background: #fff !important;
            color: #c7d0d8 !important;
            border-color: var(--btp-border) !important;
            cursor: not-allowed;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid var(--btp-border);
            padding: 0.3rem 0.8rem;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: var(--btp-primary-light);
            box-shadow: 0 0 0 0.2rem rgba(122, 15, 28, 0.12);
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            border: 1px solid var(--btp-border);
            padding: 0.2rem 0.5rem;
            font-size: 0.85rem;
        }

        /* Le CSS bootstrap5 de DataTables style déjà les tris sur thead th,
           mais on aligne la couleur des flèches de tri sur la marque. */
        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:before,
        table.dataTable thead .sorting_desc:after {
            color: var(--btp-primary-light);
            opacity: 0.6;
        }

        .empty-state {
            padding: 2rem 1rem;
            text-align: center;
            color: #9aa7b2;
        }

        .empty-state i { font-size: 2rem; margin-bottom: 0.5rem; display: block; color: #d8c8ae; }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        window.evenementConfig = {
            routes: {
                store: "{{ route('evenements.store') }}",
                update: "{{ route('evenements.update', ['evenement' => ':id']) }}",
                destroy: "{{ route('evenements.destroy', ['evenement' => ':id']) }}",
                toggleActive: "{{ route('evenements.toggleActive', ['evenement' => ':id']) }}",
                show: "{{ route('evenements.show', ['evenement' => ':id']) }}",
                getData: "{{ route('evenements.data') }}",
            },
            anneeId: {{ $anneeCourante->id }}
        };
    </script>
    <script src="{{ asset('pages/evenement.js') }}"></script>
@endpush
