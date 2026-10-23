@extends('admin.layouts.app')

@section('title', 'Lignes budgétaires')
@section('page_icon', 'fa-chart-line')
@section('page_title', 'Lignes budgétaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Lignes budgétaires</li>
@endsection

@section('page_actions')
    @can('previsions.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvellePrevision" data-bs-toggle="modal" data-bs-target="#previsionModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle ligne budgétaire
        </x-bouton>
    @endcan
@endsection

@push('css')
<style>
    .lignes-page .stat-card { background:#fff; border:1px solid var(--eim-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); height:100%; }
    .lignes-page .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .lignes-page .stat-label { font-size:.75rem; color:var(--school-muted); }
    .filtre-input { border-radius:10px; border:1px solid var(--eim-border); padding:.55rem .9rem; font-size:.88rem; }
    .table-prev thead tr { background:var(--eim-cream); }
    .table-prev thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--eim-border); white-space:nowrap; }
    .table-prev tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--eim-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-prev tfoot td { padding:.85rem 1rem; font-weight:700; background:var(--eim-hover); }
    .num { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
    .jauge { height:6px; border-radius:999px; background:var(--eim-border); overflow:hidden; margin-top:.35rem; max-width:160px; margin-left:auto; }
    .jauge > span { display:block; height:100%; background:var(--eim-success); }
    .jauge.depasse > span { background:var(--school-red); }
    .reste-ok { color:var(--eim-success); }
    .reste-depasse { color:var(--school-red); }
    #depenses-ligne thead th { font-size:.7rem; text-transform:uppercase; color:var(--school-red); background:var(--eim-cream); }
</style>
@endpush

@section('contenu')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $resteTotal = $totaux['global'] - $totaux['depenses'];
@endphp
<div class="lignes-page">
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <x-stat icone="fas fa-wallet" couleur="blue" id="total-global">{{ $fmt($totaux['global']) }} F
                <x-slot:libelle>Budget global — année {{ $annee?->libelle }}</x-slot:libelle>
            </x-stat>
        </div>
        <div class="col-sm-6 col-lg-4">
            <x-stat icone="fas fa-file-invoice-dollar" couleur="red" id="total-depenses">{{ $fmt($totaux['depenses']) }} F
                <x-slot:libelle>Dépenses imputées (décaissées)</x-slot:libelle>
            </x-stat>
        </div>
        <div class="col-sm-12 col-lg-4">
            <x-stat icone="fas fa-piggy-bank" :couleur="$resteTotal >= 0 ? 'green' : 'red'" id="total-reste">{{ $fmt(abs($resteTotal)) }} F
                <x-slot:libelle>{{ $resteTotal >= 0 ? 'Reste disponible' : 'Dépassement' }}</x-slot:libelle>
            </x-stat>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <x-recherche name="search" placeholder="Rechercher une ligne budgétaire..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.previsions.index') }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                </div>
                <div class="col-md-4 text-end">
                    <x-compteur>{{ $previsions->total() }} ligne(s)</x-compteur>
                </div>
            </form>
        </div>

        <x-tableau class="table-prev align-middle mb-0" id="table-lignes">
            <thead>
                <tr>
                    <th class="ps-4">Ligne budgétaire</th>
                    <th class="text-end">Montant global</th>
                    <th class="text-end">Dépenses</th>
                    <th class="text-end">Reste</th>
                    <th class="text-center pe-4" style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($previsions as $p)
                @php
                    $global = (float) $p->montant_previsionnel;
                    $depense = (float) $p->montant_depenses;
                    $reste = $global - $depense;
                    $taux = $global > 0 ? min(100, round($depense / $global * 100)) : 0;
                @endphp
                <tr data-ligne="{{ $p->id }}">
                    <td class="ps-4">
                        <div class="fw-semibold">{{ $p->libelle }}</div>
                        <div class="cell-sub">{{ $p->nb_depenses }} dépense(s) décaissée(s)@if ($p->centre) · <i class="fas fa-sitemap me-1"></i>{{ $p->centre->code }} — {{ $p->centre->libelle }}@endif</div>
                    </td>
                    <td class="text-end num">{{ $fmt($global) }} F</td>
                    <td class="text-end">
                        <div class="num">{{ $fmt($depense) }} F</div>
                        @if ((float) $p->montant_en_attente > 0)
                            <div class="cell-sub" title="Dépenses de la ligne pas encore décaissées : elles ne sont pas imputées">+ {{ $fmt($p->montant_en_attente) }} F en attente de décaissement ({{ $p->nb_en_attente }})</div>
                        @endif
                        <div class="jauge @if ($reste < 0) depasse @endif" title="{{ $taux }} % consommé"><span style="width:{{ $taux }}%"></span></div>
                    </td>
                    <td class="text-end">
                        @if ($reste > 0)
                            <span class="num reste-ok">{{ $fmt($reste) }} F</span>
                        @elseif ($reste < 0)
                            <span class="num reste-depasse">Dépassement {{ $fmt(-$reste) }} F</span>
                        @else
                            <span class="text-muted">Épuisé</span>
                        @endif
                    </td>
                    <td class="text-center pe-4">
                        <x-actions>
                            <li>
                                <button type="button" class="dropdown-item btn-voir-depenses"
                                        data-url="{{ route('finances.previsions.depenses', $p->id) }}" data-libelle="{{ $p->libelle }}">
                                    <i class="fas fa-list"></i> Voir les dépenses
                                </button>
                            </li>
                            @can('previsions.gerer')
                                <li>
                                    <button type="button" class="dropdown-item btn-edit-prev"
                                            data-id="{{ $p->id }}" data-libelle="{{ $p->libelle }}" data-montant="{{ $global }}" data-centre="{{ $p->centre_analytique_id }}"
                                            data-bs-toggle="modal" data-bs-target="#previsionModal">
                                        <i class="fas fa-pen"></i> Modifier
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <x-actions.supprimer :action="route('finances.previsions.destroy', $p->id)" />
                            @endcan
                        </x-actions>
                    </td>
                </tr>
            @empty
                <x-vide colspan="5" icone="fas fa-chart-line">Aucune ligne budgétaire pour l'année {{ $annee?->libelle }}.</x-vide>
            @endforelse
            </tbody>
        </x-tableau>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$previsions" /></div>
</div>

{{-- Nouvelle ligne / modification : libellé et montant global --}}
@can('previsions.gerer')
<x-modale id="previsionModal" data-bs-backdrop="static" aria-hidden="true" form-contenu croix-blanche>
    <x-slot:formulaire id="prev-form" method="POST" action="{{ route('finances.previsions.store') }}">@csrf
        <input type="hidden" name="_method" id="prev-method" value="POST"></x-slot:formulaire>
    <x-slot:titre id="prev-titre"><i class="fas fa-chart-line me-2"></i>Nouvelle ligne budgétaire</x-slot:titre>
    <div class="mb-3">
        <x-libelle for="prev-libelle" requis>Libellé</x-libelle>
        <input type="text" name="libelle" id="prev-libelle" class="form-control" maxlength="150" placeholder="Ex : Fournitures de bureau, Entretien des bâtiments...">
        <div class="text-danger small mt-1" id="libelle-error"></div>
    </div>
    <div class="mb-2">
        <x-libelle for="prev-montant" requis>Montant global (F)</x-libelle>
        <input type="number" name="montant_previsionnel" id="prev-montant" class="form-control" min="1" step="1">
        <div class="text-danger small mt-1" id="montant_previsionnel-error"></div>
    </div>
    <div class="mb-2">
        <x-libelle for="prev-centre">Centre analytique (facultatif)</x-libelle>
        <select name="centre_analytique_id" id="prev-centre" class="form-select">
            <option value="">— Aucun —</option>
            @foreach ($centres as $c)
                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->libelle }}</option>
            @endforeach
        </select>
        <div class="form-text small">Le montant de la ligne sert alors de budget à ce centre dans le rapport analytique.</div>
        <div class="text-danger small mt-1" id="centre_analytique_id-error"></div>
    </div>
    <div class="cell-sub">Ligne de l'année {{ $annee?->libelle }}. Une dépense « sur ligne budgétaire » lui est imputée une fois décaissée.</div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer</x-bouton>
    </x-slot:pied>
</x-modale>
@endcan

{{-- Dépenses d'une ligne, avec les caisses qui les ont décaissées --}}
<x-modale id="depensesLigneModal" aria-hidden="true" dialogue="modal-dialog-centered modal-xl" corps-id="depenses-ligne-contenu" croix-blanche>
    <x-slot:titre><i class="fas fa-list me-2"></i>Dépenses — <span id="depenses-ligne-titre"></span></x-slot:titre>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
    </x-slot:pied>
</x-modale>
@endsection

@push('js')
<script>
$(function () {
    const URL_LIGNES = '{{ url('finances/previsions') }}';
    const $form = $('#prev-form');

    $('#btnNouvellePrevision').on('click', function () {
        $form[0].reset();
        $form.attr('action', '{{ route('finances.previsions.store') }}');
        $('#prev-method').val('POST');
        $('#prev-titre').html('<i class="fas fa-chart-line me-2"></i>Nouvelle ligne budgétaire');
        $('#libelle-error, #montant_previsionnel-error').text('');
    });

    $(document).on('click', '.btn-edit-prev', function () {
        $form.attr('action', URL_LIGNES + '/' + $(this).data('id'));
        $('#prev-method').val('PUT');
        $('#prev-libelle').val($(this).data('libelle'));
        $('#prev-montant').val($(this).data('montant'));
        $('#prev-centre').val($(this).data('centre') || '');
        $('#prev-titre').html('<i class="fas fa-pen me-2"></i>Modifier la ligne budgétaire');
        $('#libelle-error, #montant_previsionnel-error').text('');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        $('#libelle-error, #montant_previsionnel-error').text('');
        $.ajax({ url: $form.attr('action'), method: 'POST', data: $form.serialize(), headers: { 'Accept': 'application/json' } })
            .done(res => {
                bootstrap.Modal.getInstance(document.getElementById('previsionModal'))?.hide();
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            })
            .fail(xhr => {
                const erreurs = xhr.responseJSON?.errors;
                if (erreurs) {
                    Object.keys(erreurs).forEach(champ => $('#' + champ + '-error').text(erreurs[champ][0]));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            });
    });

    $(document).on('click', '.btn-voir-depenses', function () {
        $('#depenses-ligne-titre').text($(this).data('libelle'));
        $('#depenses-ligne-contenu').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i></div>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('depensesLigneModal')).show();
        $.get($(this).data('url'))
            .done(html => $('#depenses-ligne-contenu').html(html))
            .fail(xhr => $('#depenses-ligne-contenu').html('<div class="text-danger">' + (xhr.responseJSON?.message || "Impossible d'afficher les dépenses.") + '</div>'));
    });
});
</script>
@endpush
