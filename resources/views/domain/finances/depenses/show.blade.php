@extends('admin.layouts.app')

@section('title', 'Dépense — ' . $depense->libelle)
@section('page_icon', 'fa-file-invoice-dollar')
@section('page_title', 'Détail de la dépense')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.depenses.index') }}">Dépenses</a></li>
    <li class="breadcrumb-item active">{{ \Str::limit($depense->libelle, 40) }}</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.depenses.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </x-bouton>
    @if ($imputations->isEmpty())
        @can('analytique.ventiler')
            <x-bouton variante="nouveau" type="button" data-bs-toggle="modal" data-bs-target="#ventilationModal">
                <i class="fas fa-sitemap me-1"></i> Ventiler
            </x-bouton>
        @endcan
    @else
        @can('analytique.ventiler')
            <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#ventilationModal">
                <i class="fas fa-pen me-1"></i> Re-ventiler
            </x-bouton>
        @endcan
    @endif
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow: 0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--eim-border) !important; }
    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.8rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; }
    .info-value { font-weight:600; color:var(--school-ink); }
    .badge-depense { display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:600; padding:.4em .85em; border-radius:999px; }
    .badge-depense-attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-depense-validee { background:#e3f7ec; color:var(--eim-success); }
    .badge-depense-rejetee { background:#fdecea; color:var(--school-red); }
    .centre-code { font-family:monospace; background:var(--eim-cream); color:var(--school-red); padding:2px 8px; border-radius:6px; font-size:.75rem; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .empty-state { padding:2rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
    .modal-ventil { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-ventil { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .ventil-row { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.65rem .85rem; margin-bottom:.5rem; display:flex; align-items:center; gap:.75rem; }
    .ventil-row select, .ventil-row input { border-radius:6px; border:1px solid var(--eim-border); padding:.4rem .6rem; font-size:.85rem; }
</style>
@endsection

@section('contenu')
<div class="row g-4">

    {{-- Fiche dépense --}}
    <div class="col-md-5">
        <x-carte class="mb-4">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i> Informations</h6>
            </div>
            <div class="card-body p-4">
                <div class="info-row">
                    <span class="info-label">Libellé</span>
                    <span class="info-value">{{ $depense->libelle }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bénéficiaire</span>
                    <span class="info-value">{{ $depense->beneficiaire ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-value">{{ optional($depense->date_depense)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Montant</span>
                    <span class="info-value" style="font-size:1.15rem;color:var(--school-red-dark);">
                        {{ number_format((float) $depense->montant, 2, ',', ' ') }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="badge-depense badge-depense-{{ $depense->statut_depense?->css() }}">
                        {{ $depense->statut_depense?->label() ?? '—' }}
                    </span>
                </div>
                @if ($depense->caisse)
                    <div class="info-row">
                        <span class="info-label">Caisse de règlement</span>
                        <span class="info-value">{{ $depense->caisse->libelle }}</span>
                    </div>
                @endif
                @if ($depense->motif_depense)
                    <div class="mt-3 p-2 rounded" style="background:var(--eim-cream);font-size:.85rem;color:var(--eim-ink-soft);">
                        <strong>Motif :</strong> {{ $depense->motif_depense }}
                    </div>
                @endif
                @if ($depense->justificatif_path)
                    <div class="mt-3">
                        <x-bouton variante="contour" href="{{ asset('storage/' . $depense->justificatif_path) }}" target="_blank" class="btn-sm rounded-pill px-3">
                            <i class="fas fa-paperclip me-1"></i> Voir le justificatif
                        </x-bouton>
                    </div>
                @endif
            </div>
        </x-carte>
    </div>

    {{-- Ventilation analytique --}}
    <div class="col-md-7">
        <x-carte>
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-sitemap me-1" style="color:var(--school-gold-dark);"></i> Ventilation analytique</h6>
                <x-compteur>{{ $imputations->count() }} centre(s)</x-compteur>
            </div>
            <div class="card-body p-0">
                @if ($imputations->isEmpty())
                    <x-vide icone="fas fa-sitemap" :paragraphe="false"><p class="mb-0">Cette dépense n'est imputée à aucun centre analytique.</p>
                        @can('analytique.ventiler')
                            <p class="text-muted small mb-0">Cliquez sur « Ventiler » pour répartir son montant.</p>
                        @endcan</x-vide>
                @else
                    <x-tableau class="mb-0">
                            <thead style="background:var(--eim-cream);">
                                <tr>
                                    <th class="ps-4" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Centre</th>
                                    <th class="text-end" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Montant</th>
                                    <th class="text-end" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">%</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php
                                $montantDepense = (float) $depense->montant;
                                $partDe = fn ($imp) => $imp->pourcentage !== null
                                    ? (float) $imp->pourcentage
                                    : ($montantDepense > 0 ? round((float) $imp->montant / $montantDepense * 100, 2) : null);
                            @endphp
                            @foreach ($imputations as $imp)
                                <tr>
                                    <td class="ps-4">
                                        <span class="centre-code">{{ $imp->centre?->code }}</span>
                                        <span class="ms-2">{{ $imp->centre?->libelle }}</span>
                                    </td>
                                    <td class="num">{{ number_format((float) $imp->montant, 2, ',', ' ') }}</td>
                                    <td class="num text-muted">{{ $partDe($imp) !== null ? number_format($partDe($imp), 2, ',', ' ') . ' %' : '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot style="background:var(--eim-cream);">
                                <tr>
                                    <td class="ps-4 fw-bold text-uppercase text-xs" style="color:var(--school-red-dark);">Total</td>
                                    <td class="num fw-bold">{{ number_format($imputations->sum('montant'), 2, ',', ' ') }}</td>
                                    <td class="num fw-bold">{{ number_format($imputations->sum(fn ($imp) => $partDe($imp) ?? 0), 2, ',', ' ') }} %</td>
                                </tr>
                            </tfoot>
                        </x-tableau>
                @endif
            </div>
        </x-carte>
    </div>
</div>

{{-- Modale ventilation --}}
@can('analytique.ventiler')
<x-modale id="ventilationModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-ventil" entete-class="modal-header-ventil" corps-class="p-4" croix-blanche>
    <x-slot:formulaire method="POST" action="{{ route('finances.ventiler', $depense) }}" id="ventil-form">@csrf
                <input type="hidden" name="source_type" value="depense">
                <input type="hidden" name="source_id"   value="{{ $depense->id }}">
                <input type="hidden" name="sens"        value="depense"></x-slot:formulaire>
    <x-slot:titre><i class="fas fa-sitemap me-2"></i>Ventiler la dépense</x-slot:titre>
    <div class="d-flex justify-content-between align-items-center mb-3 p-3"
         style="background:var(--eim-cream);border-radius:10px;">
        <div>
            <div class="text-muted text-xs">Dépense</div>
            <div class="fw-semibold">{{ $depense->libelle }}</div>
        </div>
        <div class="text-end">
            <div class="text-muted text-xs">Montant à ventiler</div>
            <div class="fw-bold" style="color:var(--school-red-dark);">
                {{ number_format((float) $depense->montant, 2, ',', ' ') }}
            </div>
        </div>
    </div>

    <div class="mb-3">
        <x-libelle class="small fw-semibold">Mode de ventilation</x-libelle>
        <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="mode" id="mode-montant" value="montant" checked>
            <label class="btn btn-outline-secondary" for="mode-montant">Montant fixe</label>

            <input type="radio" class="btn-check" name="mode" id="mode-pourcentage" value="pourcentage">
            <label class="btn btn-outline-secondary" for="mode-pourcentage">Pourcentage</label>
        </div>
    </div>

    <div id="ventil-rows"></div>

    <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3" id="add-row">
        <i class="fas fa-plus me-1"></i> Ajouter un centre
    </x-bouton>

    <div class="alert alert-light border-0 small mt-3 mb-0"
         style="background:var(--eim-cream);color:var(--school-muted);">
        <i class="fas fa-info-circle me-1"></i>
        <span id="ventil-hint">
            La somme des montants doit être égale à
            {{ number_format((float) $depense->montant, 2, ',', ' ') }}.
        </span>
    </div>
    <x-slot:pied class="bg-light px-4 py-3">
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer la ventilation</x-bouton>
    </x-slot:pied>
</x-modale>
@endcan

{{-- Template ligne (Blade + JS) --}}
<template id="ventil-row-template">
    <div class="ventil-row">
        <select name="imputations[__idx__][centre_analytique_id]" class="form-select" style="flex:1;" required>
            <option value="">— Centre —</option>
            @foreach ($centres as $c)
                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->libelle }}</option>
            @endforeach
        </select>
        <input type="number" step="0.01" min="0"
               name="imputations[__idx__][montant]"
               class="ventil-input-montant"
               placeholder="Montant" style="width:130px;">
        <input type="number" step="0.01" min="0" max="100"
               name="imputations[__idx__][pourcentage]"
               class="ventil-input-pourcentage"
               placeholder="%" style="width:100px; display:none;">
        <button type="button" class="btn btn-sm btn-light remove-row" title="Retirer">
            <i class="fas fa-times text-danger"></i>
        </button>
    </div>
</template>
@endsection

@push('js')
<script>
$(function () {
    const $container = $('#ventil-rows');
    const $template  = $('#ventil-row-template').html();
    let idx = 0;

    function addRow(preset) {
        const html = $template.replace(/__idx__/g, idx++);
        const $row = $(html);
        if (preset) {
            $row.find('select').val(preset.centre_analytique_id);
            $row.find('input[name$="[montant]"]').val(preset.montant ?? '');
            $row.find('input[name$="[pourcentage]"]').val(preset.pourcentage ?? '');
        }
        $container.append($row);
        syncMode();
    }

    // Remplit avec les imputations existantes, ou une ligne vierge
    @if ($imputations->isNotEmpty())
        @foreach ($imputations as $imp)
            addRow({
                centre_analytique_id: {{ $imp->centre_analytique_id }},
                montant: {{ $imp->montant ?? 'null' }},
                pourcentage: {{ $imp->pourcentage ?? 'null' }},
            });
        @endforeach
    @else
        addRow();
    @endif

    $('#add-row').on('click', () => addRow());

    $(document).on('click', '.remove-row', function () {
        if ($container.children().length > 1) {
            $(this).closest('.ventil-row').remove();
        }
    });

    function syncMode() {
        const mode = $('input[name="mode"]:checked').val();
        if (mode === 'montant') {
            $('.ventil-input-montant').show().prop('disabled', false);
            $('.ventil-input-pourcentage').hide().prop('disabled', true);
            $('#ventil-hint').html(
                'La somme des montants doit être égale à {{ number_format((float) $depense->montant, 2, ',', ' ') }}.'
            );
        } else {
            $('.ventil-input-montant').hide().prop('disabled', true);
            $('.ventil-input-pourcentage').show().prop('disabled', false);
            $('#ventil-hint').html('La somme des pourcentages doit être égale à 100 %.');
        }
    }

    $('input[name="mode"]').on('change', syncMode);

    $('#ventil-form').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        $.ajax({
            url:  $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#ventilationModal').modal('hide');
                window.showToastThenReload(res.message || 'Ventilation enregistrée.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    function clearErrors() { $('.is-invalid').removeClass('is-invalid'); }
});
</script>
@endpush