@extends('admin.layouts.app')

@section('title', 'Point de vente')
@section('page_icon', 'fa-cash-register')
@section('page_title', 'Point de vente')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.ventes.index') }}">Boutique</a></li>
    <li class="breadcrumb-item active">Caisse</li>
@endsection

@section('page_actions')
    <select id="select-magasin" class="form-select form-select-sm" style="width:auto;">
        @foreach ($magasins as $m)
            <option value="{{ $m->id }}" @selected($m->id === $magasinActif->id)>
                {{ $m->libelle }}
            </option>
        @endforeach
    </select>
    <x-bouton variante="contour" href="{{ route('logistique.dashboard', ['magasin_id' => $magasinActif->id]) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-chart-line me-1"></i> Dashboard
    </x-bouton>
@endsection

@section('css')
<style>
    .pos-page { --p-border:var(--eim-border); --p-bg-soft:var(--eim-cream); }
    .text-xs { font-size:.72rem; }

    .pos-grid { display:grid; grid-template-columns: 1.2fr .8fr; gap:1rem; }
    @media (max-width: 992px) { .pos-grid { grid-template-columns: 1fr; } }

    .pos-card { background:#fff; border:1px solid var(--p-border); border-radius:14px; overflow:hidden; box-shadow:0 4px 18px rgba(142,18,22,.05); }
    .pos-card .card-header { padding:.85rem 1.15rem; border-bottom:1px solid var(--p-border); display:flex; justify-content:space-between; align-items:center; }
    .pos-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .pos-card .card-header i { color:var(--school-gold-dark); }
    .pos-card .card-body { padding:1rem 1.15rem; }

    /* Recherche */
    .search-pos { position:relative; }
    .search-pos input { padding-left:38px; border-radius:10px; border:1px solid var(--p-border); font-size:.95rem; padding-top:.65rem; padding-bottom:.65rem; }
    .search-pos input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(227,30,36,.12); outline:none; }
    .search-pos .icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#7A6A6A; }

    .search-results { position:absolute; top:calc(100% + 6px); left:0; right:0; background:#fff; border:1px solid var(--p-border); border-radius:10px; max-height:380px; overflow-y:auto; z-index:100; box-shadow:0 10px 30px rgba(142,18,22,.12); }
    .search-results:empty { display:none; }
    .result-item { display:flex; align-items:center; gap:.75rem; padding:.65rem 1rem; cursor:pointer; border-bottom:1px solid #f5f1e8; }
    .result-item:last-child { border-bottom:none; }
    .result-item:hover { background:var(--p-bg-soft); }
    .result-thumb { width:36px; height:36px; border-radius:8px; background:var(--p-bg-soft); color:var(--school-red); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; overflow:hidden; }
    .result-thumb img { width:100%; height:100%; object-fit:cover; }
    .result-info { flex:1; min-width:0; }
    .result-name { font-weight:600; font-size:.9rem; }
    .result-meta { font-size:.75rem; color:var(--school-muted); }
    .result-price { margin-left:auto; font-weight:700; color:var(--school-red-dark); font-variant-numeric:tabular-nums; }
    .result-stock { font-size:.7rem; color:var(--school-muted); }
    .result-stock.bas { color:var(--school-red); }

    /* Panier */
    .panier-table { width:100%; border-collapse:collapse; }
    .panier-table thead th { font-size:.7rem; text-transform:uppercase; color:var(--school-red); font-weight:700; padding:.5rem .6rem; border-bottom:1px solid var(--p-border); }
    .panier-table tbody td { padding:.5rem .6rem; border-bottom:1px solid var(--p-border); font-size:.85rem; vertical-align:middle; }
    .panier-table tbody td.qte { width:80px; }
    .panier-table tbody td.qte input { width:100%; border:1px solid var(--p-border); border-radius:6px; padding:.3rem .4rem; font-size:.85rem; text-align:center; }
    .panier-table tbody td.prix { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .panier-table tbody td.rem { width:70px; }
    .panier-table tbody td.rem input { width:100%; border:1px solid var(--p-border); border-radius:6px; padding:.3rem .4rem; font-size:.85rem; text-align:center; }
    .panier-table tbody td.act { width:30px; text-align:center; }
    .btn-del { background:transparent; border:none; color:var(--school-red); cursor:pointer; }

    .panier-vide { padding:2.5rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
    .panier-vide i { font-size:2.5rem; color:#d8c8ae; margin-bottom:.5rem; display:block; }

    /* Totaux */
    .total-box { background:var(--p-bg-soft); border-radius:12px; padding:1rem 1.15rem; margin-top:.75rem; }
    .total-row { display:flex; justify-content:space-between; padding:.35rem 0; font-size:.9rem; }
    .total-row.grand { font-size:1.35rem; font-weight:700; padding-top:.65rem; margin-top:.4rem; border-top:1px solid var(--p-border); color:var(--school-red-dark); font-variant-numeric:tabular-nums; }

    .btn-encaisser {
        background:linear-gradient(135deg, var(--eim-success), #2ecc71);
        color:#fff; border:none; border-radius:10px;
        padding:1rem 1.5rem; font-weight:700; font-size:1rem; width:100%;
        box-shadow:0 4px 14px rgba(28,122,77,.28);
    }
    .btn-encaisser:hover:not(:disabled) { color:#fff; transform:translateY(-1px); }
    .btn-encaisser:disabled { opacity:.5; cursor:not-allowed; }

    /* Ventes du jour */
    .table-jour thead tr { background:var(--p-bg-soft); }
    .table-jour thead th { color:var(--school-red); font-size:.7rem; text-transform:uppercase; font-weight:700; padding:.5rem .75rem; border-bottom:1px solid var(--p-border); }
    .table-jour tbody td { padding:.5rem .75rem; border-bottom:1px solid var(--p-border); font-size:.85rem; vertical-align:middle; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
</style>
@endsection

@section('contenu')
<div class="pos-page">
    <div class="pos-grid">

        {{-- Colonne gauche : recherche + panier --}}
        <div>
            <div class="pos-card mb-3">
                <div class="card-body">
                    <div class="search-pos">
                        <i class="fas fa-search icon"></i>
                        <input type="text" id="recherche-produit" class="form-control"
                               placeholder="Scanner / rechercher un produit (nom, code, id)..."
                               autofocus autocomplete="off">
                        <div class="search-results" id="search-results"></div>
                    </div>
                </div>
            </div>

            <div class="pos-card">
                <div class="card-header">
                    <h6><i class="fas fa-shopping-cart"></i> Panier</h6>
                    <span class="text-xs text-muted" id="panier-count">0 article(s)</span>
                </div>
                <div class="card-body p-0">
                    <table class="panier-table" id="panier-table" style="display:none;">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="qte">Qté</th>
                                <th class="prix">P.U.</th>
                                <th class="rem">Rem. %</th>
                                <th class="prix">Montant</th>
                                <th class="act"></th>
                            </tr>
                        </thead>
                        <tbody id="panier-body"></tbody>
                    </table>
                    <div class="panier-vide" id="panier-vide">
                        <i class="fas fa-shopping-cart"></i>
                        <p class="mb-0">Le panier est vide.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne droite : totaux + encaissement --}}
        <div>
            <div class="pos-card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-calculator"></i> Total &amp; encaissement</h6>
                </div>
                <div class="card-body">
                    <div class="total-box">
                        <div class="total-row">
                            <span>Nombre de lignes</span>
                            <span id="total-lignes">0</span>
                        </div>
                        <div class="total-row">
                            <span>Sous-total HT</span>
                            <span id="total-ht">0,00</span>
                        </div>
                        <div class="total-row">
                            <span>Remises</span>
                            <span id="total-remise" style="color:var(--school-red);">0,00</span>
                        </div>
                        <div class="total-row grand">
                            <span>TOTAL À PAYER</span>
                            <span id="total-ttc">0,00</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <x-libelle class="small fw-semibold">Client externe (optionnel)</x-libelle>
                        <select id="client-id" class="form-select form-select-sm">
                            <option value="">— Client anonyme —</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->nom_raison_sociale }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-2">
                        <x-libelle class="small fw-semibold">Élève (optionnel)</x-libelle>
                        <select id="inscription-id" class="form-select form-select-sm">
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

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="marquer-payee" checked>
                        <label class="form-check-label small" for="marquer-payee">
                            Paiement immédiat (espèces / mobile money)
                        </label>
                    </div>

                    <button type="button" class="btn-encaisser mt-3" id="btn-encaisser" disabled>
                        <i class="fas fa-cash-register me-1"></i> ENCAISSER
                    </button>

                    <button type="button" class="btn btn-link btn-sm w-100 mt-2 text-muted" id="btn-vider">
                        <i class="fas fa-trash me-1"></i> Vider le panier
                    </button>
                </div>
            </div>

            <div class="pos-card">
                <div class="card-header">
                    <h6><i class="fas fa-history"></i> Ventes du jour</h6>
                    <span class="text-xs text-muted" id="jour-total"></span>
                </div>
                <div class="card-body p-0">
                    <table class="table-jour" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="padding-left:1rem;">Réf.</th>
                                <th>Acheteur</th>
                                <th class="num" style="padding-right:1rem;">Montant</th>
                            </tr>
                        </thead>
                        <tbody id="jour-body">
                            @forelse ($ventesDuJour as $v)
                                <tr>
                                    <td style="padding-left:1rem;font-family:monospace;font-size:.72rem;">{{ $v->reference }}</td>
                                    <td class="text-sm">{{ $v->acheteur_nom }}</td>
                                    <td class="num" style="padding-right:1rem;">{{ number_format((float) $v->total_ttc, 0, ',', ' ') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3" style="font-size:.8rem;">Aucune vente aujourd'hui.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const magasinId = {{ $magasinActif->id }};

    // ============ ÉTAT DU PANIER ============
    let panier = [];

    const fmt = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);
    const escapeHtml = (s) => $('<div>').text(s ?? '').html();

    // ============ RECHERCHE ============
    let searchTimeout, currentXhr;

    $('#recherche-produit').on('input', function () {
        const q = $(this).val().trim();
        clearTimeout(searchTimeout);
        if (currentXhr) { currentXhr.abort(); currentXhr = null; }

        if (q.length < 1) { $('#search-results').empty(); return; }

        searchTimeout = setTimeout(() => {
            currentXhr = $.ajax({
                url: '{{ route("logistique.pos.rechercher") }}',
                data: { q, magasin_id: magasinId },
                headers: { 'Accept': 'application/json' },
                success: function (res) { afficherResultats(res.produits || []); },
                error: function (xhr) { if (xhr.statusText !== 'abort') $('#search-results').empty(); }
            });
        }, 200);
    });

    function afficherResultats(produits) {
        const $c = $('#search-results').empty();
        if (produits.length === 0) {
            $c.html('<div class="result-item text-muted small">Aucun produit trouvé.</div>');
            return;
        }
        produits.forEach(p => {
            const stockClass = p.stock > 0 ? '' : 'bas';
            const stockLabel = p.stock > 0 ? `Stock : ${p.stock}` : 'RUPTURE';
            const $item = $(`
                <div class="result-item">
                    <div class="result-thumb">${p.photo ? `<img src="/storage/${p.photo}">` : '<i class="fas fa-box"></i>'}</div>
                    <div class="result-info">
                        <div class="result-name">${escapeHtml(p.libelle)}</div>
                        <div class="result-meta">
                            ${escapeHtml(p.unite_stock)} ·
                            <span class="result-stock ${stockClass}">${stockLabel}</span>
                        </div>
                    </div>
                    <div class="result-price">${fmt(p.prix_unitaire)}</div>
                </div>
            `);
            $item.on('click', () => ajouterAuPanier(p));
            $c.append($item);
        });
    }

    function ajouterAuPanier(produit) {
        if (produit.stock <= 0) {
            window.showToast(`« ${produit.libelle} » est en rupture de stock.`, 'error');
            return;
        }
        const existant = panier.find(l => l.produit_id === produit.id);
        if (existant) {
            if (existant.quantite + 1 > produit.stock) {
                window.showToast(`Stock insuffisant (${produit.stock} disponible).`, 'error');
                return;
            }
            existant.quantite += 1;
        } else {
            panier.push({
                produit_id:    produit.id,
                libelle:       produit.libelle,
                unite:         produit.unite_stock,
                prix_unitaire: parseFloat(produit.prix_unitaire),
                quantite:      1,
                remise:        0,
                stock:         parseFloat(produit.stock),
            });
        }
        $('#recherche-produit').val('').focus();
        $('#search-results').empty();
        renderPanier();
    }

    // ============ RENDU PANIER ============
    function renderPanier() {
        const $body = $('#panier-body').empty();
        const $table = $('#panier-table');
        const $vide = $('#panier-vide');

        if (panier.length === 0) {
            $table.hide();
            $vide.show();
        } else {
            $table.show();
            $vide.hide();
        }

        panier.forEach((l, idx) => {
            const montant = l.quantite * l.prix_unitaire * (1 - l.remise / 100);
            $body.append(`
                <tr>
                    <td>${escapeHtml(l.libelle)}</td>
                    <td class="qte"><input type="number" min="0.01" step="0.01" value="${l.quantite}" data-idx="${idx}" data-field="quantite"></td>
                    <td class="prix">${fmt(l.prix_unitaire)}</td>
                    <td class="rem"><input type="number" min="0" max="100" step="0.5" value="${l.remise}" data-idx="${idx}" data-field="remise"></td>
                    <td class="prix">${fmt(montant)}</td>
                    <td class="act"><button type="button" class="btn-del" data-idx="${idx}"><i class="fas fa-times"></i></button></td>
                </tr>
            `);
        });

        recalcTotaux();
    }

    function recalcTotaux() {
        let sousTotal = 0, remises = 0;
        panier.forEach(l => {
            const brut = l.quantite * l.prix_unitaire;
            const net  = brut * (1 - l.remise / 100);
            sousTotal += brut;
            remises   += brut - net;
        });

        $('#total-lignes').text(panier.length);
        $('#total-ht').text(fmt(sousTotal));
        $('#total-remise').text('− ' + fmt(remises));
        $('#total-ttc').text(fmt(sousTotal - remises));
        $('#panier-count').text(panier.length + ' article(s)');
        $('#btn-encaisser').prop('disabled', panier.length === 0);
    }

    // Modification quantité / remise
    $(document).on('input', '#panier-body input[data-field]', function () {
        const idx = $(this).data('idx');
        const field = $(this).data('field');
        let val = parseFloat($(this).val() || 0);

        if (field === 'quantite') {
            if (val < 0.01) val = 0.01;
            if (val > panier[idx].stock) {
                val = panier[idx].stock;
                window.showToast(`Quantité limitée au stock disponible (${panier[idx].stock}).`, 'error');
            }
        } else if (field === 'remise') {
            if (val < 0) val = 0;
            if (val > 100) val = 100;
        }

        panier[idx][field] = val;
        $(this).val(val);
        renderPanier();
    });

    // Suppression ligne
    $(document).on('click', '.btn-del', function () {
        const idx = $(this).data('idx');
        panier.splice(idx, 1);
        renderPanier();
    });

    $('#btn-vider').on('click', function () {
        if (panier.length === 0) return;
        if (! confirm('Vider le panier ?')) return;
        panier = [];
        renderPanier();
    });

    // ============ ENCAISSEMENT ============
    $('#btn-encaisser').on('click', function () {
        if (panier.length === 0) return;

        const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Encaissement...');

        // Onglet du ticket ouvert pendant le clic (sinon bloqué par le navigateur) ; fermé si la vente échoue.
        const fenetreTicket = window.open('', '_blank');

        const payload = {
            _token:           '{{ csrf_token() }}',
            date_vente:       '{{ now()->toDateString() }}',
            magasin_id:       magasinId,
            type_vente:       1, // comptoir
            client_id:        $('#client-id').val() || null,
            inscription_id:   $('#inscription-id').val() || null,
            marquer_payee:    $('#marquer-payee').is(':checked') ? 1 : 0,
            lignes:           panier.map(l => ({
                produit_id:    l.produit_id,
                quantite:      l.quantite,
                prix_unitaire: l.prix_unitaire,
                remise:        l.remise,
            })),
        };

        $.ajax({
            url: '{{ route("logistique.pos.vendre") }}',
            method: 'POST',
            data: payload,
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                window.showToast(res.message || 'Vente enregistrée.', 'success');
                // Affiche le ticket dans l'onglet ouvert au clic
                const urlTicket = res.data?.ticket_url || res.ticket_url;
                if (fenetreTicket && urlTicket) fenetreTicket.location = urlTicket;
                else if (fenetreTicket) fenetreTicket.close();

                // Reset panier
                panier = [];
                $('#client-id').val('');
                $('#inscription-id').val('');
                renderPanier();
                $btn.prop('disabled', false).html('<i class="fas fa-cash-register me-1"></i> ENCAISSER');
                $('#recherche-produit').focus();

                // Rafraîchit les ventes du jour
                setTimeout(() => window.location.reload(), 1200);
            },
            error: function (xhr) {
                if (fenetreTicket) fenetreTicket.close();
                $btn.prop('disabled', false).html('<i class="fas fa-cash-register me-1"></i> ENCAISSER');
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            },
        });
    });

    // Changement de magasin → recharge la page
    $('#select-magasin').on('change', function () {
        window.location.href = '{{ route("logistique.pos.index") }}?magasin_id=' + $(this).val();
    });

    // Raccourci Entrée sur le champ recherche = ajoute le premier résultat
    $('#recherche-produit').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#search-results .result-item').first().trigger('click');
        }
    });
});
</script>
@endpush