{{-- resources/views/domain/finances/cheques/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Portefeuille des chèques')
@section('page_icon', 'fa-money-check-alt')
@section('page_title', 'Banques & Chèques')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Chèques</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.cheques.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
@endsection

@section('css')
<style>
    .contenu-paiement .bloc-info { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.55rem .8rem; height:100%; }
    .contenu-paiement .bloc-titre { font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .contenu-paiement .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
    .contenu-paiement .reference { font-family:monospace; font-size:.8rem; background:var(--eim-cream); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .contenu-paiement .nature { display:inline-block; background:var(--eim-cream); color:var(--school-red); font-weight:600; font-size:.7rem; padding:.12rem .5rem; border-radius:999px; border:1px solid var(--eim-border); }
    .contenu-paiement .num { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    #lignes-paiement thead th { font-size:.7rem; text-transform:uppercase; color:var(--school-red); background:var(--eim-cream); }
    .cheques-page { --cheques-bg-soft: var(--eim-cream); --cheques-border: var(--eim-border); }
    .text-xs { font-size: .72rem; }
    .text-sm { font-size: .8rem; }

    .stat-card { background:#fff; border:1px solid var(--cheques-border); border-radius:12px; padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition: transform .2s; }
    .stat-icon { width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0; }
    .stat-info .stat-value { font-size:1.2rem; font-weight:700; line-height:1.2; color: var(--school-ink); }
    .stat-info .stat-label { font-size:.78rem; color: var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--cheques-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--cheques-border); padding:.55rem .9rem; font-size:.88rem; }

    .btn-filtrer { background: var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }

    .compteur-badge { display:inline-block; background: var(--cheques-bg-soft); color: var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--cheques-border); }

    .btn-nouveau:hover { color:#fff; transform:translateY(-2px); }
    .btn-outline-scolarite { border:1px solid var(--cheques-border); color: var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background: var(--cheques-bg-soft); border-color: var(--school-red); color: var(--school-red); }

    .table-cheques thead tr { background: var(--cheques-bg-soft); }
    .table-cheques thead th { color: var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--cheques-border); padding:.9rem 1rem; white-space:nowrap; }
    .table-cheques thead th i { color: var(--school-gold-dark); }
    .table-cheques tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--cheques-border); font-size:.9rem; color: var(--school-ink); vertical-align:middle; }
    .table-cheques tbody tr:hover { background:var(--eim-hover); }

    .cheque-numero { font-weight:700; color: var(--school-ink); display:block; font-size:.92rem; }
    .cheque-emetteur { font-size:.75rem; color: var(--school-muted); }
    .cheque-montant { font-variant-numeric: tabular-nums; font-weight:700; text-align:right; }

    .badge-cheque { display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:600; padding:.4em .85em; border-radius:999px; }
    .badge-cheque-emis     { background:#dbeafe; color:#1d4ed8; }
    .badge-cheque-encaisse { background:#e3f7ec; color:var(--eim-success); }
    .badge-cheque-rejete   { background:#fdecea; color: var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; }
    .badge-cheque-emis .badge-dot     { background:#1d4ed8; }
    .badge-cheque-encaisse .badge-dot { background:var(--eim-success); }
    .badge-cheque-rejete .badge-dot   { background: var(--school-red); }

    .empty-state { padding: 3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-cheques { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-cheques { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background: var(--cheques-bg-soft); border:1px solid var(--cheques-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color: var(--school-red); margin-bottom:.75rem; display:flex; align-items:center; gap:6px; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color: var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--cheques-border); padding:.55rem .75rem; font-size:.88rem; }

    .btn-annuler { background:#fff; border:1px solid var(--cheques-border); color:var(--eim-ink-soft); border-radius:8px; font-weight:500; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-annuler:hover { background: var(--cheques-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
    .btn-enregistrer:hover { color:#fff; }
</style>
@endsection

@section('contenu')
<div class="cheques-page">

    {{-- Cartes stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-clock" couleur="blue" libelle="En attente">{{ $compteurs['emis'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-check-circle" couleur="green" libelle="Encaissés">{{ $compteurs['encaisse'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-times-circle" couleur="red" libelle="Rejetés">{{ $compteurs['rejete'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="gold" libelle="Montant en attente">{{ number_format($montantAttente, 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    {{-- Tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Numéro, émetteur..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['statut'] ?? '') == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="banque_id" class="form-select filtre-input">
                        <option value="">— Banque —</option>
                        @foreach ($banques as $b)
                            <option value="{{ $b->id }}" @selected(($filters['banque_id'] ?? '') == $b->id)>
                                {{ $b->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input"
                           value="{{ $filters['date_debut'] ?? '' }}" placeholder="Du">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input"
                           value="{{ $filters['date_fin'] ?? '' }}" placeholder="Au">
                </div>
                <div class="col-md-1">
                    <x-bouton variante="filtrer" type="submit" class="w-100" title="Filtrer">
                        <i class="fas fa-filter"></i>
                    </x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-cheques align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-hashtag me-2"></i>Numéro · Émission</th>
                            <th><i class="fas fa-user me-2"></i>Émetteur · Banque</th>
                            <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Montant</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($cheques as $cheque)
                        <tr>
                            <td class="ps-4">
                                <span class="cheque-numero">{{ $cheque->numero }}</span>
                                <span class="cheque-emetteur">#{{ $cheque->id }}</span>
                                <div class="text-xs text-muted"><i class="far fa-calendar me-1"></i>{{ optional($cheque->date_emission)->format('d/m/Y') ?? '—' }}</div>
                            </td>
                            <td class="text-sm">
                                <div>{{ $cheque->emetteur }}</div>
                                <div class="text-xs text-muted"><i class="fas fa-university me-1"></i>{{ $cheque->banque?->nom ?? '—' }}</div>
                            </td>
                            <td>
                                @php $s = $cheque->statut; @endphp
                                <span class="badge-cheque badge-cheque-{{ $s?->css() }}">
                                    <span class="badge-dot"></span>{{ $s?->label() ?? '—' }}
                                </span>
                            </td>
                            <td class="cheque-montant">
                                {{ number_format((float) $cheque->montant, 2, ',', ' ') }}
                            </td>
                            <td class="text-center pe-4">
                                @php $paiementLie = $cheque->paiement_id ?: $cheque->paiement_lie; @endphp
                                <x-actions>
                                        @if ($paiementLie)
                                            <li>
                                                <button type="button" class="dropdown-item btn-voir-paiement"
                                                        data-url="{{ route('finances.cheques.paiement', $cheque->id) }}"
                                                        data-numero="{{ $cheque->numero }}">
                                                    <i class="fas fa-eye"></i> Voir le paiement
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                        @endif
                                        @if ($cheque->isEnAttente())
                                            @can('cheques.rapprocher')
                                                <li>
                                                    <button type="button" class="dropdown-item btn-encaisser"
                                                            data-id="{{ $cheque->id }}"
                                                            data-numero="{{ $cheque->numero }}"
                                                            data-montant="{{ number_format((float) $cheque->montant, 2, ',', ' ') }}"
                                                            data-bs-toggle="modal" data-bs-target="#rapprocheModal">
                                                        <i class="fas fa-check-circle"></i> Marquer encaissé
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger btn-rejeter"
                                                            data-id="{{ $cheque->id }}"
                                                            data-numero="{{ $cheque->numero }}"
                                                            data-bs-toggle="modal" data-bs-target="#rapprocheModal">
                                                        <i class="fas fa-times-circle"></i> Rejeter
                                                    </button>
                                                </li>
                                            @endcan
                                        @else
                                            <li>
                                                <span class="dropdown-item text-muted" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ $cheque->statut->label() }}
                                                    @if ($cheque->date_rapprochement)
                                                        — {{ $cheque->date_rapprochement->format('d/m/Y') }}
                                                    @endif
                                                </span>
                                            </li>
                                        @endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-money-check-alt" :paragraphe="false"><p class="mb-0 fw-medium">Aucun chèque enregistré.</p></x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4">
        <x-pagination :liste="$cheques->appends(request()->query())" />
    </div>

    {{-- Contenu du paiement qui a abouti au chèque --}}
    <x-modale id="paiementModal" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" corps-id="paiement-contenu" croix-blanche>
        <x-slot:titre><i class="fas fa-receipt me-2"></i>Paiement du chèque n° <span id="paiement-cheque"></span></x-slot:titre>
        <x-slot:pied>
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
        </x-slot:pied>
    </x-modale>

    {{-- Modale rapprochement (encaissé / rejeté) --}}
    @can('cheques.rapprocher')
    <x-modale id="rapprocheModal" data-bs-backdrop="static" aria-hidden="true" contenu-class="modal-cheques" entete-class="modal-header-cheques" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="rapproche-form" method="POST" action="">@csrf
                    <input type="hidden" name="statut" id="rapproche-statut" value=""></x-slot:formulaire>
        <x-slot:titre id="rapproche-title">Rapprochement</x-slot:titre>
        <div class="d-flex justify-content-between align-items-center mb-3 p-3"
             style="background: var(--cheques-bg-soft); border-radius:10px;">
            <div>
                <div class="text-muted text-xs">Chèque</div>
                <div class="fw-semibold" id="rapproche-numero">—</div>
            </div>
            <div class="text-end">
                <div class="text-muted text-xs">Montant</div>
                <div class="fw-bold fs-6" id="rapproche-montant">—</div>
            </div>
        </div>

        <x-section>
            <div class="mb-3" id="date-encaissement-bloc">
                <x-libelle>Date d'encaissement</x-libelle>
                <input type="date" name="date_encaissement" class="form-control"
                       value="{{ now()->toDateString() }}">
            </div>
            <div class="mb-0 d-none" id="motif-rejet-bloc">
                <x-libelle requis>Motif du rejet</x-libelle>
                <textarea name="motif_rejet" rows="2" class="form-control"></textarea>
                <div class="text-danger text-xs mt-1" id="motif_rejet-error"></div>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit" id="rapproche-submit">
                <i class="fas fa-check me-1"></i> Confirmer
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    // =========== Contenu du paiement d'un chèque ===========
    $(document).on('click', '.btn-voir-paiement', function () {
        $('#paiement-cheque').text($(this).data('numero'));
        $('#paiement-contenu').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i></div>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('paiementModal')).show();
        $.get($(this).data('url'))
            .done(html => $('#paiement-contenu').html(html))
            .fail(xhr => $('#paiement-contenu').html('<div class="text-danger">' + (xhr.responseJSON?.message || "Impossible d'afficher ce paiement.") + '</div>'));
    });

    // =========== Rapprochement ===========
    const $rapprocheForm = $('#rapproche-form');
    if ($rapprocheForm.length) {
        $('.btn-encaisser').on('click', function () {
            const id      = $(this).data('id');
            const numero  = $(this).data('numero');
            const montant = $(this).data('montant');

            $rapprocheForm.attr('action', '{{ url("finances/cheques") }}/' + id + '/rapprocher');
            $('#rapproche-statut').val('{{ \App\Domain\Finances\Types\ChequeStatut::ENCAISSE->value }}');
            $('#rapproche-title').html('<i class="fas fa-check-circle me-2"></i>Marquer comme encaissé');
            $('#rapproche-numero').text(numero);
            $('#rapproche-montant').text(montant);
            $('#date-encaissement-bloc').removeClass('d-none');
            $('#motif-rejet-bloc').addClass('d-none');
            $('#rapproche-submit').html('<i class="fas fa-check me-1"></i> Encaisser');
            clearErrors($rapprocheForm);
        });

        $('.btn-rejeter').on('click', function () {
            const id     = $(this).data('id');
            const numero = $(this).data('numero');

            $rapprocheForm.attr('action', '{{ url("finances/cheques") }}/' + id + '/rapprocher');
            $('#rapproche-statut').val('{{ \App\Domain\Finances\Types\ChequeStatut::REJETE->value }}');
            $('#rapproche-title').html('<i class="fas fa-times-circle me-2"></i>Rejeter le chèque');
            $('#rapproche-numero').text(numero);
            $('#rapproche-montant').text('—');
            $('#date-encaissement-bloc').addClass('d-none');
            $('#motif-rejet-bloc').removeClass('d-none');
            $('#rapproche-submit').html('<i class="fas fa-times me-1"></i> Rejeter');
            clearErrors($rapprocheForm);
        });

        $rapprocheForm.on('submit', function (e) {
            e.preventDefault();
            clearErrors($rapprocheForm);

            $.ajax({
                url: $rapprocheForm.attr('action'),
                method: 'POST',
                data: $rapprocheForm.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#rapprocheModal').modal('hide');
                    window.showToastThenReload(res.message || 'Rapprochement enregistré.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors(xhr.responseJSON.errors);
                    } else {
                        window.showToast("Une erreur est survenue.", 'error');
                    }
                }
            });
        });
    }

    // =========== Utilitaires ===========
    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[id$="-error"]').text('');
    }

    function showErrors(errors) {
        Object.keys(errors).forEach(function (field) {
            $('[name="' + field + '"]').addClass('is-invalid');
            $('#' + field + '-error').text(errors[field][0]);
        });
    }
});
</script>
@endpush