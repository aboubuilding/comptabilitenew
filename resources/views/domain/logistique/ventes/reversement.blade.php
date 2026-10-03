@extends('admin.layouts.app')

@section('title', 'Reversement boutique')
@section('page_icon', 'fa-hand-holding-usd')
@section('page_title', 'Reversement boutique au caissier')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.ventes.index') }}">Boutique</a></li>
    <li class="breadcrumb-item active">Reversement</li>
@endsection

@section('css')
<style>
    .rev-page { --r-border:#ece4d6; --r-bg-soft:#faf6ee; }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--r-border) !important; }
    .form-section { background:var(--r-bg-soft); border:1px solid var(--r-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--r-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }

    .ligne-form { display:flex; gap:.5rem; align-items:center; background:#fff; border:1px dashed var(--r-border); border-radius:10px; padding:.6rem .75rem; margin-bottom:.5rem; }
    .ligne-form input, .ligne-form select { border-radius:6px; border:1px solid var(--r-border); padding:.4rem .6rem; font-size:.85rem; }
    .ligne-total { font-weight:700; color:var(--school-red-dark); min-width:90px; text-align:right; font-variant-numeric:tabular-nums; }

    .total-box { background:#faf6ee; border-radius:12px; padding:1rem 1.15rem; margin-top:1rem; }
    .total-row { display:flex; justify-content:space-between; padding:.35rem 0; font-size:.9rem; }
    .total-row.grand { font-size:1.25rem; font-weight:700; padding-top:.6rem; border-top:1px solid var(--r-border); margin-top:.4rem; color:var(--school-red-dark); }

    .btn-valider {
        background:linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border:none; border-radius:10px;
        padding:.9rem 1.5rem; font-weight:700; font-size:.95rem; width:100%;
    }
    .btn-valider:hover { color:#fff; }
    .btn-valider:disabled { opacity:.5; cursor:not-allowed; }
</style>
@endsection

@section('contenu')
<div class="rev-page">
    <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;border-left:3px solid #2563eb;">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Boutique sans ordinateur.</strong> Saisissez ici les ventes relevées sur le carnet papier.
        Chaque ligne correspond à un article vendu. Le montant total sera versé au caissier et enregistré dans la caisse sélectionnée.
    </div>

    <form id="reversement-form">
        @csrf

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card main-card">
                    <div class="card-header bg-white py-3 px-4">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i> Informations du reversement</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-store"></i> Contexte</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date de reversement <span class="text-danger">*</span></label>
                                    <input type="date" name="date_reversement" class="form-control" value="{{ $dateJour }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Boutique source <span class="text-danger">*</span></label>
                                    <select name="magasin_id" class="form-select" required>
                                        <option value="">— Sélectionner —</option>
                                        @foreach ($magasins as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Référence carnet</label>
                                    <input type="text" name="reference_carnet" class="form-control" maxlength="50" placeholder="Ex : Carnet #A-12">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Commentaire</label>
                                    <input type="text" name="commentaire" class="form-control" maxlength="500">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-list"></i> Ventes du carnet</div>
                            <div id="lignes-rev"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="add-ligne-rev">
                                <i class="fas fa-plus me-1"></i> Ajouter un article
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card main-card">
                    <div class="card-header bg-white py-3 px-4">
                        <h6 class="mb-0"><i class="fas fa-calculator me-1" style="color:var(--school-gold-dark);"></i> Encaissement au caissier</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-section mb-3">
                            <label class="form-label">Caisse de réception <span class="text-danger">*</span></label>
                            <select name="caisse_id" id="caisse-id" class="form-select" required>
                                <option value="">— Sélectionner —</option>
                                @foreach ($caisses as $caisse)
                                    <option value="{{ $caisse->id }}">{{ $caisse->libelle }}</option>
                                @endforeach
                            </select>
                            <div class="form-text small mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                @if ($caisses->isEmpty())
                                    Aucune caisse n'est ouverte : le caissier doit ouvrir sa caisse avant le reversement.
                                @else
                                    Seules les caisses ouvertes sont proposées.
                                @endif
                            </div>
                        </div>

                        <div class="total-box">
                            <div class="total-row">
                                <span>Nombre de lignes</span>
                                <span id="rev-nb">0</span>
                            </div>
                            <div class="total-row">
                                <span>Total théorique</span>
                                <span id="rev-total">0,00</span>
                            </div>
                            <div class="total-row grand">
                                <span>Montant déclaré</span>
                                <input type="number" name="montant_declare" id="rev-declare"
                                       class="form-control form-control-sm text-end" step="0.01" min="0"
                                       style="max-width:140px;font-weight:700;color:var(--school-red-dark);" required>
                            </div>
                            <div id="rev-ecart" class="text-center small mt-2" style="display:none;"></div>
                        </div>

                        <button type="submit" class="btn-valider mt-3" id="btn-valider" disabled>
                            <i class="fas fa-check me-1"></i> Valider le reversement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="ligne-rev-template">
    <div class="ligne-form">
        <select name="lignes[__idx__][produit_id]" class="form-select ligne-produit" style="flex:1;" required>
            <option value="">— Produit —</option>
            @foreach ($produits as $produit)
                <option value="{{ $produit->id }}" data-prix="{{ (float) $produit->prix_unitaire }}">{{ $produit->libelle }}</option>
            @endforeach
        </select>
        <input type="number" name="lignes[__idx__][quantite]" step="0.01" min="0.01" placeholder="Qté" style="width:80px;" required>
        <input type="number" name="lignes[__idx__][prix_unitaire]" step="0.01" min="0" placeholder="Prix" style="width:100px;" required>
        <input type="number" name="lignes[__idx__][remise]" step="0.5" min="0" max="100" value="0" placeholder="%" style="width:70px;">
        <span class="ligne-total">0,00</span>
        <button type="button" class="btn btn-sm btn-light remove-ligne"><i class="fas fa-times text-danger"></i></button>
    </div>
</template>
@endsection

@push('js')
<script>
$(function () {
    const $container = $('#lignes-rev');
    const tpl = $('#ligne-rev-template').html();
    let idx = 0;

    function addLigne() {
        const $row = $(tpl.replace(/__idx__/g, idx++));
        $container.append($row);
        bindRow($row);
    }

    function bindRow($row) {
        const $qte = $row.find('input[name$="[quantite]"]');
        const $pu  = $row.find('input[name$="[prix_unitaire]"]');
        const $rem = $row.find('input[name$="[remise]"]');
        const $tot = $row.find('.ligne-total');

        function recalc() {
            const q = parseFloat($qte.val() || 0);
            const p = parseFloat($pu.val() || 0);
            const r = parseFloat($rem.val() || 0);
            $tot.text(new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(q * p * (1 - r / 100)));
            recalcTotal();
        }
        $qte.on('input', recalc);
        $pu.on('input', recalc);
        $rem.on('input', recalc);

        // Le choix du produit propose son prix de vente (modifiable : le carnet fait foi)
        $row.find('.ligne-produit').on('change', function () {
            const prix = $(this).find('option:selected').data('prix');
            if (prix !== undefined && prix !== '') $pu.val(prix);
            if (!$qte.val()) $qte.val(1);
            recalc();
        });
    }

    function recalcTotal() {
        let total = 0, nb = 0;
        $container.find('.ligne-form').each(function () {
            const q = parseFloat($(this).find('input[name$="[quantite]"]').val() || 0);
            const p = parseFloat($(this).find('input[name$="[prix_unitaire]"]').val() || 0);
            const r = parseFloat($(this).find('input[name$="[remise]"]').val() || 0);
            if (q > 0 && p > 0) nb++;
            total += q * p * (1 - r / 100);
        });
        $('#rev-nb').text(nb);
        $('#rev-total').text(new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(total));
        $('#rev-total').data('theorique', total);

        // Pré-remplit le montant déclaré si vide
        if (! $('#rev-declare').val()) $('#rev-declare').val(total.toFixed(2));

        verifierEcart();
        $('#btn-valider').prop('disabled', nb === 0);
    }

    function verifierEcart() {
        const theorique = parseFloat($('#rev-total').data('theorique') || 0);
        const declare = parseFloat($('#rev-declare').val() || 0);
        const ecart = +(declare - theorique).toFixed(2);

        const $box = $('#rev-ecart');
        if (Math.abs(ecart) > 0.01) {
            $box.show().html(
                `<span style="color:var(--school-red);font-weight:600;">
                    ⚠️ Écart : ${ecart > 0 ? '+' : ''}${ecart.toFixed(2)} F
                </span>`
            );
        } else {
            $box.hide();
        }
    }

    $('#rev-declare').on('input', verifierEcart);
    $('#add-ligne-rev').on('click', addLigne);
    $(document).on('click', '.remove-ligne', function () {
        if ($container.children().length > 1) {
            $(this).closest('.ligne-form').remove();
            recalcTotal();
        }
    });


    $('#reversement-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btn-valider').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');

        $.ajax({
            url: '{{ route("logistique.ventes.reversement.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                window.showToast(res.data?.message || res.message || 'Reversement enregistré.', 'success');
                setTimeout(() => window.location.href = '{{ route("logistique.ventes.index") }}', 1500);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Valider le reversement');
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

    // Init
    addLigne();
});
</script>
@endpush