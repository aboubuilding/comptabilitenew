@extends('admin.layouts.app')

@section('title', 'Boutique / Point de vente')
@section('page_icon', 'fa-store')
@section('page_title', 'Boutique / Point de vente')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Boutique</li>
@endsection

@section('page_actions')
    <a href="{{ route('logistique.ventes.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('logistique.clients.index') }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-user-friends me-1"></i> Clients
    </a>
    @can('ventes.creer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleVente"
                data-bs-toggle="modal" data-bs-target="#venteModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle vente
        </button>
    @endcan
@endsection

@section('css')
<style>
    .ventes-page { --vt-bg-soft:#faf6ee; --vt-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--vt-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--vt-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--vt-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background:var(--school-red-dark); color:#fff; }
    .compteur-badge { display:inline-block; background:var(--vt-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--vt-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--vt-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--vt-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-ventes thead tr { background:var(--vt-bg-soft); }
    .table-ventes thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--vt-border); white-space:nowrap; }
    .table-ventes tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--vt-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-ventes tbody tr:hover { background:#fdfaf5; }

    .reference { font-family:monospace; font-size:.8rem; background:var(--vt-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.impaye { background:#fdecea; color:var(--school-red); }
    .badge-statut.paye   { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.annule { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-vente { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-vente { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--vt-bg-soft); border:1px solid var(--vt-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--vt-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--vt-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }

    .ligne-form { display:flex; gap:.5rem; align-items:center; background:#fff; border:1px dashed var(--vt-border); border-radius:10px; padding:.6rem .75rem; margin-bottom:.5rem; }
    .ligne-form input, .ligne-form select { border-radius:6px; border:1px solid var(--vt-border); padding:.4rem .6rem; font-size:.85rem; }
    .ligne-total { font-weight:700; color:var(--school-red-dark); min-width:90px; text-align:right; font-variant-numeric:tabular-nums; }
</style>
@endsection

@section('contenu')
<div class="ventes-page">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-receipt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['jour_nb'] }}</div>
                    <div class="stat-label">Ventes aujourd'hui</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['jour_montant'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Montant du jour</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['mois_nb'] }}</div>
                    <div class="stat-label">Ventes ce mois</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['mois_montant'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Montant du mois</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Référence, acheteur..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="magasin_id" class="form-select filtre-input">
                        <option value="">— Magasin —</option>
                        @foreach ($magasins as $m)
                            <option value="{{ $m->id }}" @selected(($filters['magasin_id'] ?? '') == $m->id)>{{ $m->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="statut_paiement" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut_paiement'] ?? '') === (string) $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter"></i></button>
                    <a href="{{ route('logistique.ventes.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ventes align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Référence</th>
                            <th>Date</th>
                            <th>Acheteur</th>
                            <th>Type</th>
                            <th class="text-end">Montant TTC</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($ventes as $vente)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('logistique.ventes.show', $vente) }}" class="text-decoration-none">
                                    <span class="reference">{{ $vente->reference }}</span>
                                </a>
                            </td>
                            <td class="text-sm text-muted">{{ optional($vente->date_vente)->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $vente->acheteur_nom }}</div>
                                @if ($vente->client)
                                    <div class="text-xs text-muted">{{ $vente->client->telephone }}</div>
                                @elseif ($vente->inscription?->eleve?->matricule)
                                    <div class="text-xs text-muted">{{ $vente->inscription->eleve->matricule }}</div>
                                @endif
                            </td>
                            <td class="text-sm">{{ $vente->type_vente?->label() }}</td>
                            <td class="num grand">{{ number_format((float) $vente->total_ttc, 2, ',', ' ') }}</td>
                            <td>
                                <span class="badge-statut {{ $vente->statut_paiement?->css() }}">
                                    <span class="badge-dot"></span>{{ $vente->statut_paiement?->label() }}
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logistique.ventes.show', $vente) }}">
                                                <i class="fas fa-eye"></i> Voir détail
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logistique.ventes.ticket', $vente) }}" target="_blank">
                                                <i class="fas fa-receipt"></i> Imprimer le ticket
                                            </a>
                                        </li>
                                        @if (! $vente->isPayee() && ! $vente->isAnnulee())
                                            @can('ventes.annuler')
                                                <li>
                                                    <form action="{{ route('logistique.ventes.marquerPayee', $vente) }}" method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Marquer cette vente comme payée ?">
                                                        @csrf
                                                        <button class="dropdown-item"><i class="fas fa-check"></i> Marquer payée</button>
                                                    </form>
                                                </li>
                                            @endcan
                                        @endif
                                        @if (! $vente->isAnnulee())
                                            @can('ventes.annuler')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger btn-annuler-vente"
                                                            data-id="{{ $vente->id }}"
                                                            data-reference="{{ $vente->reference }}"
                                                            data-bs-toggle="modal" data-bs-target="#annulerVenteModal">
                                                        <i class="fas fa-times"></i> Annuler la vente
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-store"></i><p>Aucune vente enregistrée.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $ventes->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modale nouvelle vente --}}
    @can('ventes.creer')
    <div class="modal fade" id="venteModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-vente">
                <form id="vente-form">
                    @csrf
                    <div class="modal-header modal-header-vente">
                        <h5 class="modal-title"><i class="fas fa-store me-2"></i>Nouvelle vente</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations générales</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_vente" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Magasin <span class="text-danger">*</span></label>
                                    <select name="magasin_id" class="form-select" required>
                                        <option value="">— Sélectionner —</option>
                                        @foreach ($magasins as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type</label>
                                    <select name="type_vente" class="form-select">
                                        @foreach ($types as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Client externe</label>
                                    <select name="client_id" class="form-select">
                                        <option value="">— Aucun —</option>
                                        @foreach ($clients as $c)
                                            <option value="{{ $c->id }}">{{ $c->nom_raison_sociale }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text small">
                                        Ou laissez vide et liez un élève ci-dessous.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Élève</label>
                                    <select name="inscription_id" class="form-select">
                            <option value="">— Aucun élève —</option>
                            @foreach ($inscriptions as $inscription)
                                <option value="{{ $inscription->id }}">
                                    {{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}
                                    @if ($inscription->eleve->matricule) ({{ $inscription->eleve->matricule }}) @endif
                                    — {{ $inscription->classe?->libelle ?? $inscription->niveau?->libelle ?? 'sans classe' }}
                                </option>
                            @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-list"></i> Produits vendus</div>
                            <div id="lignes-vente"></div>
                            <button type="button" class="btn btn-outline-scolarite btn-sm mt-2" id="add-ligne-vente">
                                <i class="fas fa-plus me-1"></i> Ajouter un produit
                            </button>
                            <div class="alert alert-light border-0 small mt-3 mb-0" style="background:#fff;">
                                <div class="d-flex justify-content-between">
                                    <span><strong>Total HT</strong></span>
                                    <span id="total-vente" style="color:var(--school-red-dark);font-weight:700;">0,00</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Le stock sera automatiquement décrémenté.</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="marquer_payee" value="1" id="marquer-payee" checked>
                            <label class="form-check-label small" for="marquer-payee">
                                Marquer comme payée immédiatement
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Enregistrer la vente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- Modale annulation --}}
    @can('ventes.annuler')
    <div class="modal fade" id="annulerVenteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-vente">
                <form id="annuler-vente-form" method="POST" action="">
                    @csrf
                    <div class="modal-header" style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Annuler la vente</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p>Annuler la vente <strong id="annuler-vente-ref">—</strong> ?</p>
                        <p class="text-muted small mb-3">
                            Le stock sera automatiquement réintégré au magasin d'origine.
                        </p>
                        <div class="form-section">
                            <label class="form-label small fw-semibold">Motif <span class="text-danger">*</span></label>
                            <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer l'annulation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
</div>

<template id="ligne-vente-template">
    <div class="ligne-form">
        <select name="lignes[__idx__][produit_id]" class="form-select" style="flex:1;" required>
            <option value="">— Produit —</option>
            @foreach ($produits as $p)
                <option value="{{ $p->id }}" data-prix="{{ $p->prix_unitaire }}">{{ $p->libelle }}</option>
            @endforeach
        </select>
        <input type="number" name="lignes[__idx__][quantite]" step="0.01" min="0.01" placeholder="Qté" style="width:80px;" required>
        <input type="number" name="lignes[__idx__][prix_unitaire]" step="0.01" min="0" placeholder="Prix" style="width:100px;" required>
        <input type="number" name="lignes[__idx__][remise]" step="0.01" min="0" max="100" placeholder="% rem." style="width:80px;" value="0">
        <span class="ligne-total">0,00</span>
        <button type="button" class="btn btn-sm btn-light remove-ligne"><i class="fas fa-times text-danger"></i></button>
    </div>
</template>
@endsection

@push('js')
<script>
$(function () {
    const $form = $('#vente-form');
    if ($form.length) {
        const $container = $('#lignes-vente');
        const tpl = $('#ligne-vente-template').html();
        let idx = 0;

        function addLigne() {
            const $row = $(tpl.replace(/__idx__/g, idx++));
            $container.append($row);
            bindRow($row);
        }

        function bindRow($row) {
            const $prod = $row.find('select[name$="[produit_id]"]');
            const $qte  = $row.find('input[name$="[quantite]"]');
            const $pu   = $row.find('input[name$="[prix_unitaire]"]');
            const $rem  = $row.find('input[name$="[remise]"]');
            const $tot  = $row.find('.ligne-total');

            // Auto-remplit le prix à la sélection du produit
            $prod.on('change', function () {
                const prix = parseFloat($(this).find('option:selected').data('prix') || 0);
                if (prix > 0) $pu.val(prix);
                recalc();
            });

            function recalc() {
                const q = parseFloat($qte.val() || 0);
                const p = parseFloat($pu.val() || 0);
                const r = parseFloat($rem.val() || 0);
                const tot = q * p * (1 - r / 100);
                $tot.text(new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(tot));
                recalcGlobal();
            }

            $qte.on('input', recalc);
            $pu.on('input', recalc);
            $rem.on('input', recalc);
        }

        function recalcGlobal() {
            let total = 0;
            $container.find('.ligne-form').each(function () {
                const q = parseFloat($(this).find('input[name$="[quantite]"]').val() || 0);
                const p = parseFloat($(this).find('input[name$="[prix_unitaire]"]').val() || 0);
                const r = parseFloat($(this).find('input[name$="[remise]"]').val() || 0);
                total += q * p * (1 - r / 100);
            });
            $('#total-vente').text(new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(total));
        }

        $('#add-ligne-vente').on('click', addLigne);
        $(document).on('click', '.remove-ligne', function () {
            if ($container.children().length > 1) {
                $(this).closest('.ligne-form').remove();
                recalcGlobal();
            }
        });

        $('#btnNouvelleVente').on('click', function () {
            $container.empty();
            addLigne();
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: '{{ route('logistique.ventes.store') }}',
                method: 'POST',
                data: $form.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#venteModal').modal('hide');
                    window.showToastThenReload(res.message || 'Vente enregistrée.', 'success');
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

        // Init
        addLigne();
    }

    // Annulation
    $('.btn-annuler-vente').on('click', function () {
        const id = $(this).data('id');
        $('#annuler-vente-ref').text($(this).data('reference'));
        $('#annuler-vente-form').attr('action', `{{ url('logistique/ventes') }}/${id}/annuler`);
    });

    $('#annuler-vente-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#annulerVenteModal').modal('hide');
                window.showToastThenReload(res.message || 'Vente annulée.', 'success');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                window.showToast(msg, 'error');
            }
        });
    });
});
</script>
@endpush