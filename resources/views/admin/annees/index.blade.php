{{-- resources/views/admin/annees/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Années scolaires')

@section('page_title')
    <i class="fas fa-calendar-alt"></i> Années scolaires
@endsection

@section('page_actions')
    <div class="page-actions-wrapper">
        <button type="button" class="btn-btp btn-nouveau" data-bs-toggle="modal" data-bs-target="#anneeModal" id="btnNouveau">
            <i class="fas fa-plus-circle"></i> Nouvelle année
        </button>
    </div>
@endsection

@section('contenu')
    <div class="row">
        <div class="col-12">
            <div class="card-btp shadow-sm annees-card">
                <div class="card-header-btp bg-white border-0 py-4 px-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label filtre-label"><i class="fas fa-toggle-on"></i> Statut</label>
                            <select id="filterActive" class="form-select filtre-input">
                                <option value="">Tous</option>
                                <option value="1">Actif</option>
                                <option value="0">Inactif</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label filtre-label"><i class="fas fa-search"></i> Recherche</label>
                            <div class="search-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="searchInput" class="form-control filtre-input search-input" placeholder="Libellé...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button id="btnFiltrer" class="btn btn-filtrer w-100"><i class="fas fa-filter"></i> Filtrer</button>
                        </div>
                        <div class="col-md-3 text-end">
                            <span class="compteur-badge" id="anneeCount">0 année(s)</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-btp table-hover align-middle mb-0" id="anneesTable">
                            <thead>
                            <tr>
                                <th><i class="fas fa-tag"></i> Libellé</th>
                                <th><i class="fas fa-calendar-day"></i> Date rentrée</th>
                                <th><i class="fas fa-calendar-check"></i> Date fin</th>
                                <th><i class="fas fa-calendar-plus"></i> Ouv. inscriptions</th>
                                <th><i class="fas fa-calendar-times"></i> Ferm. réinscriptions</th>
                                <th><i class="fas fa-power-off"></i> Active</th>
                                <th><i class="fas fa-lock"></i> Statut</th>
                                <th class="text-center" style="width:100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody id="anneesTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout / Modification -->
    <div class="modal fade" id="anneeModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-btp">
                <div class="modal-header modal-header-btp">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-calendar-plus me-2"></i>Nouvelle année scolaire</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="anneeForm">
                        <input type="hidden" id="anneeId">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations générales</div>
                                    <div class="mb-0">
                                        <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                        <input type="text" id="libelle" class="form-control" placeholder="Ex : Année scolaire 2025-2026" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-sliders-h"></i> Statut</div>
                                    <div class="mb-3">
                                        <label class="switch-check">
                                            <input type="checkbox" id="est_active" value="1" checked>
                                            <span class="switch-slider"></span>
                                            <span class="switch-label">Active</span>
                                        </label>
                                    </div>
                                    <div class="mb-0">
                                        <label class="switch-check">
                                            <input type="checkbox" id="est_cloturee" value="1">
                                            <span class="switch-slider switch-slider-warning"></span>
                                            <span class="switch-label">Clôturée</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-calendar-week"></i> Période</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date rentrée <span class="text-danger">*</span></label>
                                    <input type="date" id="date_rentree" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date fin <span class="text-danger">*</span></label>
                                    <input type="date" id="date_fin" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section mb-0">
                            <div class="form-section-title"><i class="fas fa-calendar-alt"></i> Inscriptions</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Ouverture des inscriptions</label>
                                    <input type="date" id="date_ouverture_inscription" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fermeture des réinscriptions</label>
                                    <input type="date" id="date_fermeture_reinscription" class="form-control">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-enregistrer" id="saveAnneeBtn"><i class="fas fa-save me-1"></i> Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détail -->
    <div class="modal fade" id="detailModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content modal-btp">
                <div class="modal-header modal-header-btp">
                    <h5 class="modal-title" id="detailModalTitle"><i class="fas fa-circle-info me-2"></i>Détail année</h5>
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

@section('css')
    <style>
        /* Palette alignée sur l'identité rouge/or de l'EIM (cf. login,
           header, frais-ecoles) — remplace l'ancienne teinte bleu marine
           "BTP" pour une cohérence visuelle sur tout l'admin. */
        :root {
            --btp-primary: #7a0f1c;
            --btp-primary-light: #b31c2b;
            --btp-accent: #d4a94d;
            --btp-accent-dark: #b8860b;
            --btp-success: #1c7a4d;
            --btp-warning: #b8720b;
            --btp-danger: #c81e3a;
            --btp-border: #ece4d6;
            --btp-bg-soft: #faf6ee;
            --btp-text: #1f2d3a;
            --btp-text-muted: #6f7e8c;
        }

        .page-actions-wrapper {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
        }

        .annees-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }
        .card-header-btp {
            border-bottom: 1px solid var(--btp-border) !important;
        }
        .filtre-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #5b6b7a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 6px;
        }
        .filtre-label i { color: var(--btp-primary); margin-right: 4px; }
        .filtre-input {
            border-radius: 10px;
            border: 1px solid var(--btp-border);
            padding: 0.55rem 0.9rem;
        }
        .filtre-input:focus {
            border-color: var(--btp-primary-light);
            box-shadow: 0 0 0 0.2rem rgba(122, 15, 28, 0.12);
        }
        .search-wrapper { position: relative; }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa7b2;
            font-size: 0.85rem;
        }
        .search-input { padding-left: 34px !important; }

        .btn-filtrer {
            background: var(--btp-primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.55rem 1rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-filtrer:hover { background: var(--btp-primary-light); color: #fff; transform: translateY(-1px); }

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
            padding: 0.6rem 1.2rem;
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
            padding: 0.9rem 1rem;
            white-space: nowrap;
        }
        .table-btp thead th i { margin-right: 6px; color: var(--btp-accent-dark); }
        .table-btp tbody td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--btp-border);
            color: #33424f;
            font-size: 0.85rem;
        }
        .table-btp tbody tr {
            transition: background 0.15s ease;
        }
        .table-btp tbody tr:hover {
            background: #fdfaf5;
        }
        .table-btp tbody tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.35em 0.75em;
            border-radius: 999px;
        }
        .badge-active { background-color: #e3f7ec; color: #1c7a4d; }
        .badge-active::before { content: "\f058"; font-family: "Font Awesome 5 Free"; font-weight: 900; }
        .badge-inactive { background-color: #fbeaea; color: #b13b3b; }
        .badge-inactive::before { content: "\f057"; font-family: "Font Awesome 5 Free"; font-weight: 900; }
        .badge-success { background-color: #e3f7ec; color: #1c7a4d; }
        .badge-warning { background-color: #fdf1dc; color: #97650f; }
        .badge-danger { background-color: #fbeaea; color: #b13b3b; }

        .action-dropdown-btn {
            border-radius: 8px;
            border: 1px solid var(--btp-border);
            background: #fff;
            color: #5b6b7a;
            width: 34px;
            height: 34px;
            transition: all 0.15s ease;
        }
        .action-dropdown-btn:hover { background: var(--btp-bg-soft); border-color: var(--btp-primary-light); color: var(--btp-primary); }
        .dropdown-menu { border-radius: 10px; border: 1px solid var(--btp-border); box-shadow: 0 8px 24px rgba(122, 15, 28, 0.10); padding: 6px; }
        .dropdown-item { border-radius: 6px; font-size: 0.88rem; padding: 0.5rem 0.75rem; }
        .dropdown-item i { width: 18px; margin-right: 6px; color: #8fa0ad; }
        .dropdown-item:hover { background: var(--btp-bg-soft); }
        .dropdown-item.text-danger i { color: var(--btp-danger); }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #9aa7b2;
        }
        .empty-state i { font-size: 2.2rem; margin-bottom: 0.75rem; display: block; color: #d8c8ae; }

        .modal-btp { border: none; border-radius: 16px; overflow: hidden; }
        .modal-header-btp {
            background: linear-gradient(135deg, var(--btp-primary) 0%, var(--btp-primary-light) 100%);
            color: #fff;
            border-bottom: none;
            padding: 1.1rem 1.5rem;
        }
        .modal-header-btp .modal-title { font-weight: 600; font-size: 1.05rem; }

        .form-section {
            background: var(--btp-bg-soft);
            border: 1px solid var(--btp-border);
            border-radius: 12px;
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
        }
        .form-section-title {
            font-size: 0.78rem;
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
        }
        .form-section .form-control:focus {
            border-color: var(--btp-primary-light);
            box-shadow: 0 0 0 0.2rem rgba(122, 15, 28, 0.1);
        }

        .switch-check {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            background: #fff;
            border: 1px solid var(--btp-border);
            border-radius: 10px;
            padding: 0.6rem 0.8rem;
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
        /* "Active" reste verte pour rester intuitive ; "Clôturée" reste
           ambre (--btp-warning) — inchangé, le sens était déjà correct. */
        .switch-check input:checked + .switch-slider { background: var(--btp-success); }
        .switch-check input:checked + .switch-slider-warning { background: var(--btp-warning); }
        .switch-check input:checked + .switch-slider::before { transform: translateX(18px); }
        .switch-label { font-size: 0.9rem; font-weight: 500; color: #33424f; }

        .modal-footer {
            border-top: 1px solid var(--btp-border);
            padding: 1rem 1.5rem;
        }
        .btn-annuler {
            background: #fff;
            border: 1px solid var(--btp-border);
            color: #5b6b7a;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1.1rem;
        }
        .btn-annuler:hover { background: var(--btp-bg-soft); color: #33424f; }
        .btn-enregistrer {
            background: linear-gradient(135deg, var(--btp-primary) 0%, var(--btp-primary-light) 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1.3rem;
            transition: all 0.2s ease;
        }
        .btn-enregistrer:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(122, 15, 28, 0.3); color: #fff; }

        .toast-btp {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #fff;
            color: #1f2d3a;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.18);
            opacity: 0;
            transform: translateY(-16px) scale(0.98);
            transition: all 0.3s cubic-bezier(.4,0,.2,1);
            z-index: 9999;
            border-left: 4px solid var(--btp-accent);
            font-weight: 500;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 360px;
        }
        .toast-btp.show { opacity: 1; transform: translateY(0) scale(1); }
        .toast-btp.toast-danger { border-left-color: var(--btp-danger); }
        .toast-btp.toast-warning { border-left-color: var(--btp-warning); }
        .toast-btp span { font-size: 1rem; }
    </style>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.anneesConfig = {
            annees: @json($annees->toArray()),
            stats: @json($stats ?? []),
            routes: {
                store: "{{ route('annees.store') }}",
                update: "{{ route('annees.update', ['annee' => ':id']) }}",
                destroy: "{{ route('annees.destroy', ['annee' => ':id']) }}",
                toggleActive: "{{ route('annees.toggleActive', ['annee' => ':id']) }}",
                setActive: "{{ route('annees.setActive', ['annee' => ':id']) }}",
                show: "{{ route('annees.show', ['annee' => ':id']) }}",
                toggleStatus: "{{ route('annees.toggleStatus', ['annee' => ':id']) }}",
                stats: "{{ route('annees.stats') }}",
            }
        };
    </script>
    <script src="{{ asset('pages/annee.js') }}"></script>
@endsection
