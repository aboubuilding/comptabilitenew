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
    <x-bouton variante="contour" href="{{ route('logistique.ventes.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.clients.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-user-friends me-1"></i> Clients
    </x-bouton>
    @can('ventes.creer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelleVente" data-bs-toggle="modal" data-bs-target="#venteModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle vente
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .ventes-page { --vt-bg-soft:var(--eim-cream); --vt-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--vt-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--vt-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--vt-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--vt-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--vt-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--vt-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--vt-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-ventes thead tr { background:var(--vt-bg-soft); }
    .table-ventes thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--vt-border); white-space:nowrap; }
    .table-ventes tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--vt-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-ventes tbody tr:hover { background:var(--eim-hover); }

    .reference { font-family:monospace; font-size:.8rem; background:var(--vt-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }


    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-vente { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-vente { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--vt-bg-soft); border:1px solid var(--vt-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--vt-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--vt-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
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
            <x-stat icone="fas fa-receipt" couleur="gold" libelle="Ventes aujourd'hui">{{ $stats['jour_nb'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-coins" couleur="green" libelle="Montant du jour">{{ number_format($stats['jour_montant'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-chart-line" couleur="blue" libelle="Ventes ce mois">{{ $stats['mois_nb'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-money-bill-wave" couleur="green" libelle="Montant du mois">{{ number_format($stats['mois_montant'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    {{-- Tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Référence, acheteur..." value="{{ $filters['search'] ?? '' }}" />
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
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter"></i></x-bouton>
                    <x-bouton variante="contour" href="{{ route('logistique.ventes.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-ventes align-middle mb-0">
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
                                <x-badge class="{{ $vente->statut_paiement?->css() }}" point>{{ $vente->statut_paiement?->label() }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
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
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-store">Aucune vente enregistrée.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$ventes->appends(request()->query())" /></div>

    {{-- Modale nouvelle vente --}}
    @can('ventes.creer')
    <x-modale id="venteModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-vente" entete-class="modal-header-vente" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="vente-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-store me-2"></i>Nouvelle vente</x-slot:titre>
        <x-section titre="Informations générales" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle requis>Date</x-libelle>
                    <input type="date" name="date_vente" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Magasin</x-libelle>
                    <select name="magasin_id" class="form-select" required>
                        <option value="">— Sélectionner —</option>
                        @foreach ($magasins as $m)
                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <x-libelle>Type</x-libelle>
                    <select name="type_vente" class="form-select">
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Client externe</x-libelle>
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
                    <x-libelle>Élève</x-libelle>
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
        </x-section>

        <x-section titre="Produits vendus" icone="fas fa-list">
            <div id="lignes-vente"></div>
            <x-bouton variante="contour" type="button" class="btn-sm mt-2" id="add-ligne-vente">
                <i class="fas fa-plus me-1"></i> Ajouter un produit
            </x-bouton>
            <div class="alert alert-light border-0 small mt-3 mb-0" style="background:#fff;">
                <div class="d-flex justify-content-between">
                    <span><strong>Total HT</strong></span>
                    <span id="total-vente" style="color:var(--school-red-dark);font-weight:700;">0,00</span>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">Le stock sera automatiquement décrémenté.</span>
                </div>
            </div>
        </x-section>

        <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="marquer_payee" value="1" id="marquer-payee" checked>
            <label class="form-check-label small" for="marquer-payee">
                Marquer comme payée immédiatement
            </label>
        </div>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer la vente</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modale annulation --}}
    @can('ventes.annuler')
    <x-modale id="annulerVenteModal" aria-hidden="true" contenu-class="modal-vente" entete-style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="annuler-vente-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-exclamation-triangle me-2"></i>Annuler la vente</x-slot:titre>
        <p>Annuler la vente <strong id="annuler-vente-ref">—</strong> ?</p>
        <p class="text-muted small mb-3">
            Le stock sera automatiquement réintégré au magasin d'origine.
        </p>
        <x-section>
            <x-libelle class="small fw-semibold" requis>Motif</x-libelle>
            <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer l'annulation</button>
        </x-slot:pied>
    </x-modale>
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