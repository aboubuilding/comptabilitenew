{{-- resources/views/admin/finances/paiements/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Paiement · ' . $eleve->nom)
@section('page_icon', 'fa-cash-register')
@section('page_title', 'Paiement — ' . $eleve->nom . ' ' . $eleve->prenom)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.paiements.index') }}">Finances</a></li>
    <li class="breadcrumb-item active">{{ $eleve->nom }} {{ $eleve->prenom }}</li>
@endsection

@section('css')
<style>
    .paiements-page { --p-bg-soft: #faf6ee; --p-border: #ece4d6; }

    .zone-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; margin-bottom: 1.25rem; overflow: hidden; }
    .zone-header { background: var(--p-bg-soft); border-bottom: 1px solid var(--p-border); padding: .9rem 1.25rem; font-weight: 700; color: var(--school-red); font-size: .88rem; text-transform: uppercase; letter-spacing: .03em; }
    .zone-header i { margin-right: 8px; color: var(--school-gold-dark); }
    .zone-body { padding: 1rem 1.25rem; }

    .table-engagements thead th { font-size: .74rem; text-transform: uppercase; color: var(--school-muted); font-weight: 700; border-bottom: 2px solid var(--p-border); padding: .5rem .6rem; }
    .table-engagements tbody td { padding: .6rem; border-bottom: 1px solid var(--p-border); font-size: .88rem; vertical-align: middle; }
    .engagement-montant-input { width: 110px; border-radius: 8px; border: 1px solid var(--p-border); padding: .3rem .5rem; text-align: right; }
    .engagement-absent { color: var(--school-muted); font-size: .85rem; }
    .engagement-absent a { color: var(--school-red); font-weight: 600; }

    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa7b2; }
    .search-input { padding-left: 36px !important; border-radius: 10px; border: 1px solid var(--p-border); padding: .6rem .9rem; width: 100%; }
    .search-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .search-results { position: absolute; z-index: 50; left: 0; right: 0; top: calc(100% + 4px); background: #fff; border: 1px solid var(--p-border); border-radius: 10px; box-shadow: 0 10px 24px rgba(0,0,0,.12); max-height: 260px; overflow-y: auto; display: none; }
    .search-result-row { display: flex; justify-content: space-between; align-items: center; padding: .6rem .9rem; cursor: pointer; }
    .search-result-row:hover { background: var(--p-bg-soft); }
    .search-result-badge { font-size: .68rem; text-transform: uppercase; font-weight: 700; color: var(--school-gold-dark); }

    .panier-row { display: flex; justify-content: space-between; align-items: center; padding: .5rem .7rem; border: 1px solid var(--p-border); border-radius: 8px; margin-bottom: .4rem; font-size: .87rem; }
    .panier-row .btn-remove { color: var(--school-red); background: none; border: none; }

    .total-box { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-radius: 12px; padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; }
    .total-box .total-value { font-family: var(--school-ff-display); font-size: 1.6rem; font-weight: 700; }

    .btn-enregistrer-paiement { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border: none; border-radius: 10px; padding: .7rem 1.5rem; font-weight: 700; }
    .btn-enregistrer-paiement:hover { color: #fff; box-shadow: 0 6px 16px rgba(200,30,58,.3); }
</style>
@endsection

@section('contenu')
    <div class="paiements-page">
        <form id="paiement-form" method="POST" action="{{ route('finances.paiements.store') }}">
            @csrf
            <input type="hidden" name="eleve_id" value="{{ $eleve->id }}">

            {{-- ZONE 1 — Engagements existants --}}
            <div class="zone-card">
                <div class="zone-header"><i class="fas fa-list-check"></i>Engagements existants</div>
                <div class="zone-body">
                    <table class="table table-engagements mb-0">
                        <thead>
                            <tr>
                                <th style="width:30px;"></th>
                                <th>Nature</th>
                                <th class="text-end">Engagé</th>
                                <th class="text-end">Déjà payé</th>
                                <th class="text-end">Montant à payer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($engagements as $eng)
                                <tr>
                                    <td>
                                        @if ($eng['reference_id'])
                                            <input type="checkbox" class="engagement-checkbox form-check-input"
                                                   data-nature="{{ $eng['nature'] }}" data-reference-id="{{ $eng['reference_id'] }}"
                                                   {{ $eng['montant_du'] <= 0 ? 'disabled' : '' }}>
                                        @endif
                                    </td>
                                    <td>{{ $eng['label'] }}</td>
                                    <td class="text-end">
                                        @if (!is_null($eng['montant_engage']))
                                            {{ number_format($eng['montant_engage'], 0, ',', ' ') }} F
                                        @else
                                            <span class="engagement-absent">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ !is_null($eng['montant_paye']) ? number_format($eng['montant_paye'], 0, ',', ' ') . ' F' : '—' }}</td>
                                    <td class="text-end">
                                        @if ($eng['reference_id'])
                                            @if ($eng['montant_du'] > 0)
                                                <input type="number" step="0.01" class="engagement-montant-input"
                                                       value="{{ $eng['montant_du'] }}" max="{{ $eng['montant_du'] }}" min="0">
                                            @else
                                                <span class="text-success small">Soldé</span>
                                            @endif
                                        @else
                                            <span class="engagement-absent">
                                                Aucun — <a href="{{ $eng['lien_souscription'] }}">souscrire</a>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Aucune inscription validée pour cette année.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ZONE 2 — Panier libre --}}
            <div class="zone-card">
                <div class="zone-header"><i class="fas fa-shopping-cart"></i>Ajouter au panier (produit, service, événement)</div>
                <div class="zone-body">
                    <div class="search-wrapper mb-3">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="panier-search" class="search-input" placeholder="Rechercher un produit, un service, un événement...">
                        <div class="search-results" id="panier-search-results"></div>
                    </div>
                    <div id="panier-list"></div>
                    <p class="text-muted small mb-0" id="panier-empty-msg">Panier vide.</p>
                </div>
            </div>

            {{-- ZONE 3 — Total & enregistrement --}}
            <div class="zone-card">
                <div class="zone-header"><i class="fas fa-receipt"></i>Enregistrer le paiement</div>
                <div class="zone-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Payeur <span class="text-danger">*</span></label>
                            <input type="text" name="payeur" class="form-control" value="{{ old('payeur', $eleve->nom . ' ' . $eleve->prenom) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone_payeur" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                            <select name="mode_paiement" class="form-select">
                                @foreach (\App\Domain\Finances\Types\ModePaiement::options() as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="total-box mb-3">
                        <span>Total à payer</span>
                        <span class="total-value" id="total-a-payer">0 F</span>
                    </div>

                    <button type="submit" class="btn btn-enregistrer-paiement w-100">
                        <i class="fas fa-check-circle me-2"></i>Enregistrer le paiement
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('js')
<script>
$(function () {
    const searchUrl = '{{ route('finances.paiements.recherche-panier') }}';
    let panier = [];
    let searchTimeout;

    function formatMontant(v) {
        return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' F';
    }

    function recalculerTotal() {
        let total = 0;
        $('.engagement-checkbox:checked').each(function () {
            const montant = parseFloat($(this).closest('tr').find('.engagement-montant-input').val()) || 0;
            total += montant;
        });
        panier.forEach(item => total += item.montant * (item.quantite || 1));
        $('#total-a-payer').text(formatMontant(total));
    }

    $(document).on('change input', '.engagement-checkbox, .engagement-montant-input', recalculerTotal);

    // ===== Recherche panier =====
    $('#panier-search').on('input', function () {
        clearTimeout(searchTimeout);
        const q = $(this).val();
        if (q.length < 2) { $('#panier-search-results').hide().empty(); return; }

        searchTimeout = setTimeout(() => {
            $.getJSON(searchUrl, { q: q }, function (data) {
                const $box = $('#panier-search-results').empty();
                if (!data.length) {
                    $box.append('<div class="search-result-row text-muted">Aucun résultat</div>').show();
                    return;
                }
                data.forEach(item => {
                    const $row = $(`
                        <div class="search-result-row">
                            <div>
                                <span class="search-result-badge">${item.nature}</span><br>
                                ${item.libelle}
                            </div>
                            <strong>${formatMontant(item.montant)}</strong>
                        </div>
                    `);
                    $row.on('click', () => {
                        ajouterAuPanier(item);
                        $box.hide();
                        $('#panier-search').val('');
                    });
                    $box.append($row);
                });
                $box.show();
            });
        }, 300);
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.search-wrapper').length) {
            $('#panier-search-results').hide();
        }
    });

    function ajouterAuPanier(item) {
        panier.push({
            nature: item.nature, id: item.id, libelle: item.libelle,
            montant: item.montant, quantite: item.nature === 'produit' ? 1 : null,
        });
        renderPanier();
    }

    function renderPanier() {
        const $list = $('#panier-list').empty();
        $('#panier-empty-msg').toggle(panier.length === 0);

        panier.forEach((item, idx) => {
            const qteHtml = item.nature === 'produit'
                ? `<input type="number" min="1" value="${item.quantite}" class="form-control form-control-sm d-inline-block mx-2" style="width:60px;" data-idx="${idx}" id="panier-qte-${idx}">`
                : '';

            const $row = $(`
                <div class="panier-row">
                    <span><span class="search-result-badge">${item.nature}</span> ${item.libelle} ${qteHtml}</span>
                    <span>
                        <strong>${formatMontant(item.montant * (item.quantite || 1))}</strong>
                        <button type="button" class="btn-remove" data-idx="${idx}"><i class="fas fa-times-circle"></i></button>
                    </span>
                </div>
            `);
            $list.append($row);
        });

        $list.find('.btn-remove').on('click', function () {
            panier.splice($(this).data('idx'), 1);
            renderPanier();
            recalculerTotal();
        });
        $list.find('input[id^="panier-qte-"]').on('input', function () {
            panier[$(this).data('idx')].quantite = parseInt($(this).val()) || 1;
            renderPanier();
            recalculerTotal();
        });

        recalculerTotal();
    }

    // ===== Soumission : injecte les champs dynamiques avant envoi =====
    $('#paiement-form').on('submit', function (e) {
        $(this).find('.dynamic-field').remove();

        let idx = 0;
        $('.engagement-checkbox:checked').each(function () {
            const $tr = $(this).closest('tr');
            appendHidden(`engagements[${idx}][nature]`, $(this).data('nature'));
            appendHidden(`engagements[${idx}][reference_id]`, $(this).data('reference-id'));
            appendHidden(`engagements[${idx}][montant]`, $tr.find('.engagement-montant-input').val());
            idx++;
        });

        panier.forEach((item, i) => {
            appendHidden(`panier[${i}][nature]`, item.nature);
            appendHidden(`panier[${i}][id]`, item.id);
            appendHidden(`panier[${i}][montant]`, item.montant * (item.quantite || 1));
            if (item.quantite) appendHidden(`panier[${i}][quantite]`, item.quantite);
        });

        if (idx === 0 && panier.length === 0) {
            e.preventDefault();
            window.showToast('Sélectionnez au moins un engagement ou ajoutez un article au panier.', 'error');
        }
    });

    function appendHidden(name, value) {
        $('#paiement-form').append(`<input type="hidden" class="dynamic-field" name="${name}" value="${value}">`);
    }

    renderPanier();
});
</script>
@endpush