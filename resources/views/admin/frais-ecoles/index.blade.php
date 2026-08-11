{{-- resources/views/admin/frais-ecoles/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Frais d\'école')

@section('page_title')
    <i class="fas fa-money-bill-wave"></i> Frais d'école
    <span class="badge bg-info ms-2">{{ $anneeCourante->libelle ?? 'Année en cours' }}</span>
@endsection

@section('page_actions')
    <div class="page-actions-wrapper">
        <button type="button" class="btn-btp btn-nouveau" data-bs-toggle="modal" data-bs-target="#fraisModal" id="btnNouveau">
            <i class="fas fa-plus-circle"></i> Nouveau frais
        </button>
    </div>
@endsection

@section('contenu')
    @php
        $typesPaiement = [
            1  => "Frais d'inscription",
            2  => "Frais de scolarité",
            3  => "Services",
            4  => "Produit",
            5  => "Livre",
            6  => "Caution",
            7  => "Bus",
            8  => "Cantine",
            9  => "Autres",
            10 => "Frais d'assurance",
            11 => "Frais extrascolaire",
            12 => "Frais d'examen",
        ];
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card-btp shadow-sm frais-card">
                <!-- Filtres -->
                <div class="card-header-btp bg-white border-0 py-3 px-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label filtre-label"><i class="fas fa-tag"></i> Type</label>
                            <select id="filterType" class="form-select filtre-input">
                                <option value="">Tous</option>
                                @foreach($typesPaiement as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label filtre-label"><i class="fas fa-layer-group"></i> Niveau</label>
                            <select id="filterNiveau" class="form-select filtre-input">
                                <option value="">Tous</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}">{{ $niveau->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label filtre-label"><i class="fas fa-calendar-alt"></i> Échéancier</label>
                            <select id="filterEcheancier" class="form-select filtre-input">
                                <option value="">Tous</option>
                                <option value="1">Avec échéancier</option>
                                <option value="0">Sans échéancier</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label filtre-label"><i class="fas fa-toggle-on"></i> Statut</label>
                            <select id="filterActive" class="form-select filtre-input">
                                <option value="">Tous</option>
                                <option value="1">Actif</option>
                                <option value="0">Inactif</option>
                            </select>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="compteur-badge" id="fraisCount">0 frais</span>
                        </div>
                    </div>
                </div>

                <!-- Tableau -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-btp table-hover align-middle mb-0" id="fraisTable">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th><i class="fas fa-tag"></i> Libellé</th>
                                <th><i class="fas fa-money-bill"></i> Montant</th>
                                <th><i class="fas fa-list"></i> Type</th>
                                <th><i class="fas fa-layer-group"></i> Niveau</th>
                                <th><i class="fas fa-calendar-alt"></i> Échéancier</th>
                                <th><i class="fas fa-power-off"></i> Statut</th>
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
    <div class="modal fade" id="fraisModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content modal-btp">
                <div class="modal-header modal-header-btp">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Nouveau frais</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="fraisForm">
                        <input type="hidden" id="fraisId">
                        <input type="hidden" id="annee_id" value="{{ $anneeCourante->id }}">

                        <div class="row g-3">
                            <!-- Informations générales -->
                            <div class="col-md-6">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations générales</div>

                                    <div class="mb-3">
                                        <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                        <input type="text" id="libelle" class="form-control" placeholder="Ex : Frais d'inscription 2025" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Montant (FCFA)</label>
                                        <input type="number" id="montant" class="form-control" placeholder="0" step="0.01" min="0">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Type de paiement <span class="text-danger">*</span></label>
                                        <select id="type_paiement" class="form-control" required>
                                            <option value="">Sélectionner</option>
                                            @foreach($typesPaiement as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Type de forfait</label>
                                        <select id="type_forfait" class="form-control">
                                            <option value="">Sélectionner</option>
                                            <option value="1">Forfait fixe</option>
                                            <option value="2">Forfait variable</option>
                                            <option value="3">À l'unité</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuration -->
                            <div class="col-md-6">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-cog"></i> Configuration</div>

                                    <div class="mb-3">
                                        <label class="form-label">Niveau</label>
                                        <select id="niveau_id" class="form-control">
                                            <option value="">Sélectionner un niveau</option>
                                            @foreach($niveaux as $niveau)
                                                <option value="{{ $niveau->id }}">{{ $niveau->libelle }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Laissez vide pour appliquer à tous les niveaux</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Année scolaire</label>
                                        <input type="text" class="form-control" value="{{ $anneeCourante->libelle }}" disabled>
                                        <small class="text-muted">Les frais sont créés pour l'année en cours</small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" id="has_echeancier" class="form-check-input">
                                            <label class="form-check-label" for="has_echeancier">
                                                <i class="fas fa-calendar-alt"></i> Payable en tranches (échéancier)
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3" id="echeancierContainer" style="display:none;">
                                        <label class="form-label">Plan d'échéancier</label>
                                        <select id="plan_echeancier_id" class="form-control">
                                            <option value="">Sélectionner un plan</option>
                                            @foreach($plans as $plan)
                                                <option value="{{ $plan->id }}">{{ $plan->nom }} ({{ $plan->lignes->count() }} tranches)</option>
                                            @endforeach
                                            <option value="new">+ Créer un nouveau plan</option>
                                        </select>
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

                        <!-- Section Plan d'échéancier -->
                        <div id="planEcheancierSection" style="display:none;" class="mt-3">
                            <div class="form-section">
                                <div class="form-section-title"><i class="fas fa-calendar-alt"></i> Plan d'échéancier</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nom du plan <span class="text-danger">*</span></label>
                                        <input type="text" id="plan_nom" class="form-control" placeholder="Ex : Paiement en 3 tranches">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Année</label>
                                        <input type="text" class="form-control" value="{{ $anneeCourante->libelle }}" disabled>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea id="plan_description" class="form-control" rows="2" placeholder="Description du plan"></textarea>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label">Lignes d'échéancier</label>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm" id="lignesTable">
                                            <thead class="bg-light">
                                            <tr>
                                                <th style="width:50px;">Ordre</th>
                                                <th>Libellé</th>
                                                <th style="width:120px;">Montant</th>
                                                <th style="width:100px;">%</th>
                                                <th style="width:120px;">Jour échéance</th>
                                                <th style="width:120px;">Date échéance</th>
                                                <th style="width:40px;"></th>
                                            </tr>
                                            </thead>
                                            <tbody id="lignesTbody">
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm ligne-ordre" value="1" min="1"></td>
                                                <td><input type="text" class="form-control form-control-sm ligne-libelle" placeholder="Tranche 1"></td>
                                                <td><input type="number" class="form-control form-control-sm ligne-montant" step="0.01" placeholder="0" min="0"></td>
                                                <td><input type="number" class="form-control form-control-sm ligne-pourcentage" step="0.01" placeholder="0" min="0" max="100"></td>
                                                <td><input type="number" class="form-control form-control-sm ligne-jour" min="1" max="31" placeholder="1-31"></td>
                                                <td><input type="date" class="form-control form-control-sm ligne-date"></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger btn-remove-ligne">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <td colspan="7">
                                                    <button type="button" class="btn btn-sm btn-primary" id="btnAddLigne">
                                                        <i class="fas fa-plus"></i> Ajouter une ligne
                                                    </button>
                                                </td>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-enregistrer" id="saveFraisBtn"><i class="fas fa-save me-1"></i> Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détail -->
    <div class="modal fade" id="detailModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-btp">
                <div class="modal-header modal-header-btp">
                    <h5 class="modal-title" id="detailModalTitle"><i class="fas fa-circle-info me-2"></i>Détail du frais</h5>
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
    <style>
        /* ============================================================
           Palette locale — alignée sur l'identité rouge/or de l'EIM.
           Ces variables --btp-* n'étaient définies nulle part dans le
           layout : les dégradés des boutons ("Nouveau frais",
           "Enregistrer"...) retombaient donc sur un fond transparent,
           avec un texte en blanc -> invisible sur fond blanc.
           ============================================================ */
        /* :root garantit que ces couleurs s'appliquent à toute la page —
           bouton "Nouveau frais" et filtres (rendus par le layout hors des
           modales) inclus, pas seulement aux modales. */
        :root {
            --btp-primary       : #7a0f1c;
            --btp-primary-light : #b31c2b;
            --btp-accent        : #d4a94d;
            --btp-accent-dark   : #b8860b;
            --btp-danger        : #c81e3a;
            --btp-warning       : #b8720b;
            --btp-success       : #1c7a4d;
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

        .frais-card {
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
            outline: none;
        }

        .compteur-badge {
            display: inline-block;
            background: var(--btp-bg-soft);
            color: var(--btp-primary);
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.45rem 0.9rem;
            border-radius: 999px;
            border: 1px solid var(--btp-border);
        }

        /* ===== Boutons principaux ===== */
        .btn-btp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-nouveau {
            background: linear-gradient(135deg, var(--btp-primary) 0%, var(--btp-primary-light) 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.55rem 1.3rem;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 14px rgba(122, 15, 28, 0.28);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .btn-nouveau:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(122, 15, 28, 0.38);
            filter: brightness(1.05);
            color: #fff;
        }
        .btn-nouveau:active { transform: translateY(0); }
        .btn-nouveau:focus-visible {
            outline: 3px solid rgba(212, 169, 77, 0.55);
            outline-offset: 2px;
        }

        .table-btp thead tr { background: var(--btp-bg-soft); }

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
            padding: 0.65rem 0.8rem;
            border-bottom: 1px solid var(--btp-border);
            color: var(--btp-text);
            font-size: 0.85rem;
            vertical-align: middle;
        }

        .table-btp tbody tr:hover { background: #fdfaf5; }
        .table-btp tbody tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.32rem 0.65rem;
            border-radius: 999px;
        }

        .badge-success { background-color: #e3f7ec; color: #1c7a4d; }
        .badge-danger { background-color: #fbeaea; color: #b13b3b; }
        .badge-info { background-color: #fdecee; color: #7a0f1c; }
        .badge-warning { background-color: #fdf1dc; color: #97650f; }
        .badge-primary { background-color: #f3e6cf; color: #8a6410; }
        .badge-secondary { background-color: #e9ecef; color: #5b6b7a; }
        .badge-dark { background-color: #d1d5d9; color: #1a1f2e; }

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

        .dropdown-item i { width: 18px; margin-right: 6px; color: var(--btp-text-muted); }
        .dropdown-item:hover { background: var(--btp-bg-soft); }
        .dropdown-item.text-danger i { color: var(--btp-danger); }

        /* ===== Modales ===== */
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

        .form-section .form-control,
        .form-section .form-control:not([class*="form-control-sm"]) {
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
            padding: 0.45rem 1.2rem;
            font-size: 0.85rem;
            transition: background .15s ease, color .15s ease;
        }

        .btn-annuler:hover { background: var(--btp-bg-soft); color: var(--btp-text); }

        .btn-enregistrer {
            background: linear-gradient(135deg, var(--btp-primary) 0%, var(--btp-primary-light) 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.45rem 1.4rem;
            font-size: 0.85rem;
            box-shadow: 0 4px 12px rgba(122, 15, 28, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-enregistrer:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(122, 15, 28, 0.35);
            color: #fff;
        }

        .toast-btp {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #fff;
            color: var(--btp-text, #1f2d3a);
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.18);
            opacity: 0;
            transform: translateY(-16px) scale(0.98);
            transition: all 0.3s cubic-bezier(.4,0,.2,1);
            z-index: 9999;
            border-left: 4px solid var(--btp-accent, #d4a94d);
            font-weight: 500;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 360px;
        }

        .toast-btp.show { opacity: 1; transform: translateY(0) scale(1); }
        .toast-btp.toast-danger { border-left-color: var(--btp-danger, #c81e3a); }
        .toast-btp.toast-warning { border-left-color: var(--btp-warning, #b8720b); }
        .toast-btp span { font-size: 0.9rem; }

        /* DataTables */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { padding: 0.5rem 0.8rem; }
        .dataTables_wrapper .dataTables_info { padding: 0.5rem 0.8rem; font-size: 0.85rem; color: var(--btp-text-muted, #6f7e8c); }
        .dataTables_wrapper .dataTables_paginate { padding: 0.5rem 0.8rem; }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.3rem 0.7rem;
            font-size: 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--btp-border, #ece4d6);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--btp-primary, #7a0f1c) !important;
            color: #fff !important;
            border-color: var(--btp-primary, #7a0f1c) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--btp-primary-light, #b31c2b) !important;
            color: #fff !important;
            border-color: var(--btp-primary-light, #b31c2b) !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid var(--btp-border, #ece4d6);
            padding: 0.3rem 0.8rem;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            border: 1px solid var(--btp-border, #ece4d6);
            padding: 0.2rem 0.5rem;
            font-size: 0.85rem;
        }

        .empty-state { padding: 2.5rem 1rem; text-align: center; color: #9aa7b2; }
        .empty-state i { font-size: 2.2rem; margin-bottom: 0.6rem; display: block; color: #d8c8ae; }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        window.fraisConfig = {
            routes: {
                store: "{{ route('frais-ecoles.store') }}",
                update: "{{ route('frais-ecoles.update', ['frais_ecole' => ':id']) }}",
                destroy: "{{ route('frais-ecoles.destroy', ['frais_ecole' => ':id']) }}",
                toggleActive: "{{ route('frais-ecoles.toggleActive', ['frais_ecole' => ':id']) }}",
                show: "{{ route('frais-ecoles.show', ['frais_ecole' => ':id']) }}",
                getData: "{{ route('frais-ecoles.data') }}",
                getPlans: "{{ route('frais-ecoles.getPlans') }}",
            },
            anneeId: {{ $anneeCourante->id }}
        };
    </script>
    <script src="{{ asset('pages/frais-ecole.js') }}"></script>
@endpush
