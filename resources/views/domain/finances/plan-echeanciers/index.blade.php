{{-- resources/views/admin/finances/plan-echeanciers/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', "Plans d'échéancier")
@section('page_icon', 'fa-calendar-alt')
@section('page_title', "Plans d'échéancier")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.frais-ecoles.index') }}">Finances</a></li>
    <li class="breadcrumb-item active">Plans d'échéancier</li>
@endsection

@section('page_actions')
    <x-bouton variante="nouveau" href="{{ route('finances.plan-echeanciers.non-a-jour') }}" id="btn-non-a-jour">
        <i class="fas fa-user-clock me-1"></i> Élèves non à jour
    </x-bouton>
    {{-- Un plan se crée avec son frais, depuis l'écran des frais scolaires --}}
    <x-bouton variante="contour" href="{{ route('finances.frais-ecoles.index', ['onglet' => 'scolarite']) }}" class="btn-sm rounded-pill px-3" id="lien-frais">
        <i class="fas fa-tags me-1"></i> Frais scolaires
    </x-bouton>
@endsection

@section('css')
<style>
    .plans-page { --plans-bg-soft: var(--eim-cream); --plans-border: var(--eim-border); }
    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(142,18,22,.06); background: #fff; overflow: hidden; }
    .table-plans thead tr { background: var(--plans-bg-soft); }
    .table-plans thead th { color: var(--school-red); font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid var(--plans-border); padding: .85rem .9rem; }
    .table-plans tbody td { padding: .8rem .9rem; border-bottom: 1px solid var(--plans-border); font-size: .88rem; }
    .table-plans tbody tr:hover { background: var(--eim-hover); }
    /* Largeurs fixées : le tableau tient dans la page, les textes longs passent à la ligne */
    .table-plans { table-layout: fixed; width: 100%; }
    .table-plans thead th, .table-plans tbody td { padding-left: .7rem; padding-right: .7rem; }
    .table-plans th.col-plan   { width: 21%; }
    .table-plans th.col-frais  { width: 19%; }
    .table-plans th.col-total  { width: 105px; }
    .table-plans th.col-actions { width: 92px; }
    .table-plans tbody td { overflow-wrap: anywhere; }
    .table-plans td.frais-lies .frais-info { display: block; font-size: .74rem; color: var(--school-muted); line-height: 1.3; }
    .table-plans td.echeances .badge-lignes-count { float: right; margin-left: .4rem; padding: .15em .55em; font-size: .72rem; }
    .table-echeances { border-collapse: collapse; font-size: .8rem; }
    .table-plans .table-echeances td { padding: .1rem .6rem .1rem 0; border: none !important; font-size: .8rem; overflow-wrap: normal; }
    .table-echeances .ech-montant, .table-echeances .ech-date { white-space: nowrap; }
    .table-echeances .ech-nom { color: var(--school-muted); }
    .table-echeances .ech-montant { font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
    .table-echeances .ech-date { color: var(--school-muted); }
    .table-plans tbody tr:last-child td { border-bottom: none; }
    .badge-lignes-count { background: var(--plans-bg-soft); color: var(--school-red); font-weight: 700; font-size: .8rem; padding: .3em .7em; border-radius: 999px; border: 1px solid var(--plans-border); }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(227,30,36,.35); }
    .modal-plans { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-plans { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
    .form-section { background: var(--plans-bg-soft); border: 1px solid var(--plans-border); border-radius: 12px; padding: 1rem 1.1rem; }
    .form-section .form-control { border-radius: 8px; border: 1px solid var(--plans-border); }
    .btn-annuler { background: #fff; border: 1px solid var(--plans-border); color: var(--eim-ink-soft); border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--plans-bg-soft); }
</style>
@endsection

@section('contenu')
    <div class="plans-page">
        <div class="d-flex align-items-center gap-2 mb-3" id="annee-plans">
            <span class="badge-lignes-count" style="font-size:.8rem; padding:.35rem .8rem;"><i class="fas fa-calendar-alt me-1"></i>Année scolaire : {{ $annee?->libelle }}</span>
            <form method="GET" class="ms-auto" style="max-width:280px; width:100%;">
                <x-recherche name="search" placeholder="Nom du plan..." value="{{ $filters['search'] ?? '' }}" />
            </form>
        </div>

        {{-- Les anciennes tranches de l'année se convertissent en plans d'échéancier (rien n'est supprimé) --}}
        @php
            $aConvertir = count($conversion['a_convertir']);
            $tranchesAConvertir = array_sum(array_map(fn ($c) => count($c['tranches']), $conversion['a_convertir']));
        @endphp
        @if ($aConvertir > 0 || $conversion['incoherences'])
            <div class="alert d-flex align-items-start gap-3 mb-3" id="conversion-tranches" style="background:#fdf4e0; border:1px solid #ecd9a6; border-radius:12px;">
                <i class="fas fa-layer-group mt-1" style="color:var(--school-gold-dark);"></i>
                <div class="flex-grow-1 small">
                    <strong>Tranches de paiement à reprendre</strong>
                    <div>
                        <span id="conversion-nb-frais">{{ $aConvertir }}</span> frais de {{ $annee?->libelle }} ont encore
                        <span id="conversion-nb-tranches">{{ $tranchesAConvertir }}</span> tranche(s) non converties en plan d'échéancier.
                        @if ($conversion['deja_convertis']) {{ $conversion['deja_convertis'] }} frais sont déjà convertis.@endif
                    </div>
                    @if ($conversion['incoherences'])
                        <ul class="mb-0 mt-1 text-danger" id="conversion-incoherences">
                            @foreach ($conversion['incoherences'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @can('frais.gerer')
                    @if ($aConvertir > 0)
                        <x-bouton variante="enregistrer" type="button" class="btn-sm" id="btn-convertir-tranches">
                            <i class="fas fa-exchange-alt me-1"></i> Convertir les tranches
                        </x-bouton>
                    @endif
                @endcan
            </div>
        @endif

        <x-carte>
            <div class="card-body p-0">
                <x-tableau class="table-plans align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="col-plan">Plan</th>
                                <th class="col-frais">Frais lié</th>
                                <th>Échéances : montant et date butoir</th>
                                <th class="text-end col-total">Total</th>
                                <th class="text-center col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($plans as $plan)
                                @php
                                    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
                                    $fraisLies = $plan->fraisEcoles;
                                    // Montant d'une échéance : celui du plan, sinon son pourcentage du frais (un seul frais lié)
                                    $base = $fraisLies->count() === 1 ? (float) $fraisLies->first()->montant : null;
                                    $montantDe = fn ($ligne) => (float) $ligne->montant > 0
                                        ? (float) $ligne->montant
                                        : ($base !== null && $ligne->pourcentage ? round($base * (float) $ligne->pourcentage / 100) : null);
                                    $montants = $plan->lignes->map($montantDe);
                                    $total = $montants->contains(null) ? null : $montants->sum();
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $plan->nom }}</strong>
                                        @if ($plan->description)
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($plan->description, 60) }}</div>
                                        @endif
                                    </td>
                                    <td class="frais-lies">
                                        @forelse ($fraisLies as $frais)
                                            <div>
                                                <a href="{{ route('finances.frais-ecoles.show', $frais) }}" class="fw-semibold text-decoration-none" style="color:var(--school-ink);">{{ $frais->libelle }}</a>
                                                <span class="frais-info">{{ $frais->niveau?->libelle ?? $frais->type_paiement?->label() }} · {{ $fmt($frais->montant) }} F</span>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Aucun frais rattaché</span>
                                        @endforelse
                                    </td>
                                    <td class="echeances">
                                        <span class="badge-lignes-count" title="Nombre d'échéances">{{ $plan->lignes_count }}</span>
                                        <table class="table-echeances">
                                            @foreach ($plan->lignes as $ligne)
                                                @php $montant = $montantDe($ligne); @endphp
                                                <tr>
                                                    <td class="ech-nom">{{ $ligne->libelle }}</td>
                                                    <td class="ech-montant">
                                                        @if ($montant !== null)
                                                            {{ $fmt($montant) }} F
                                                        @elseif ($ligne->pourcentage)
                                                            {{ rtrim(rtrim(number_format((float) $ligne->pourcentage, 2, ',', ' '), '0'), ',') }} %
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td class="ech-date">
                                                        @if ($ligne->date_echeance)
                                                            <i class="far fa-calendar me-1"></i>{{ $ligne->date_echeance->format('d/m/Y') }}
                                                        @elseif ($ligne->jour_echeance)
                                                            le {{ $ligne->jour_echeance }} du mois
                                                        @else
                                                            sans date
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                    <td class="text-end fw-bold" style="color:var(--school-red-dark); white-space:nowrap;">
                                        {{ $total !== null ? $fmt($total) . ' F' : '—' }}
                                    </td>
                                    <td class="text-center">
                                        <x-actions>
                                                <li>
                                                    <a class="dropdown-item lien-suivi-eleves" href="{{ route('finances.plan-echeanciers.eleves', $plan) }}">
                                                        <i class="fas fa-user-check"></i> Élèves à jour / en retard
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-plan"
                                                            data-id="{{ $plan->id }}"
                                                            data-lecture="{{ auth()->user()->can('frais.gerer') ? 0 : 1 }}"
                                                            data-bs-toggle="modal" data-bs-target="#planModal">
                                                        @can('frais.gerer')
                                                            <i class="fas fa-pen"></i> Modifier le plan et ses échéances
                                                        @else
                                                            <i class="fas fa-eye"></i> Consulter les échéances
                                                        @endcan
                                                    </button>
                                                </li>
                                                @can('frais.gerer')
                                                <li><hr class="dropdown-divider"></li>
                                                <x-actions.supprimer :action="route('finances.plan-echeanciers.destroy', $plan)" icone="fas fa-box-archive" data-confirm-title="Archiver ce plan ?" data-confirm-text="Un plan encore rattaché à des frais ne peut pas être archivé.">Archiver</x-actions.supprimer>
                                                @endcan
                                            </x-actions>
                                    </td>
                                </tr>
                            @empty
                                <x-vide colspan="5" icone="fas fa-calendar-alt" :paragraphe="false"><p class="mb-0">Aucun plan d'échéancier pour {{ $annee?->libelle }}. Un plan se crée en définissant un frais échelonné dans les frais scolaires.</p></x-vide>
                            @endforelse
                        </tbody>
                    </x-tableau>
            </div>
        </x-carte>
        <div class="mt-3"><x-pagination :liste="$plans" /></div>

        <x-modale id="planModal" data-bs-backdrop="static" aria-hidden="true" dialogue="modal-lg" contenu-class="modal-plans" entete-class="modal-header-plans" corps-class="p-4" croix-blanche>
            <x-slot:formulaire id="plan-form" :data-lecture-seule="auth()->user()->cannot('frais.gerer')">@csrf
                        <input type="hidden" name="_method" id="plan-method" value="POST"></x-slot:formulaire>
            <x-slot:titre id="plan-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouveau plan</x-slot:titre>
            <x-section>
                <div class="mb-3">
                    <x-libelle requis>Nom</x-libelle>
                    <input type="text" name="nom" id="plan-nom" class="form-control" placeholder="Ex : 3 tranches trimestrielles">
                    <div class="text-danger small mt-1" id="plan-nom-error"></div>
                </div>
                <div class="mb-0">
                    <x-libelle>Description</x-libelle>
                    <textarea name="description" id="plan-description" class="form-control" rows="2"></textarea>
                </div>
            </x-section>

            {{-- Échéances du plan : au moins une, et 100 % au total --}}
            <x-section class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <x-libelle class="mb-0" requis>Échéances</x-libelle>
                    <x-bouton variante="annuler" type="button" class="btn-sm" id="plan-ajouter-ligne">
                        <i class="fas fa-plus me-1"></i> Ajouter une échéance
                    </x-bouton>
                </div>
                <table class="table table-sm align-middle mb-2" id="plan-lignes">
                    <thead>
                        <tr>
                            <th style="width:38px;">#</th>
                            <th>Libellé</th>
                            <th style="width:170px;">Date d'échéance</th>
                            <th style="width:120px;">Part (%)</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-semibold">Total</td>
                            <td class="fw-bold" id="plan-total">0 %</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="text-danger small" id="plan-lignes-error"></div>
            </x-section>
            <x-slot:pied>
                <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
                <x-bouton type="submit" id="plan-enregistrer"><i class="fas fa-save me-1"></i>Enregistrer</x-bouton>
            </x-slot:pied>
        </x-modale>
    </div>
@endsection

@push('js')
<script>
$(function () {
    const modalEl  = document.getElementById('planModal');
    const modal    = new bootstrap.Modal(modalEl);
    const $form    = $('#plan-form');
    const $title   = $('#plan-modal-title');
    const $method  = $('#plan-method');
    const $nom     = $('#plan-nom');
    const $nomError = $('#plan-nom-error');

    const storeUrl = '{{ route('finances.plan-echeanciers.store') }}';
    let currentId  = null;

    const $lignes       = $('#plan-lignes tbody');
    const $lignesError  = $('#plan-lignes-error');
    const $total        = $('#plan-total');

    function ajouterLigne(ligne = {}) {
        const $tr = $(`
            <tr class="plan-ligne">
                <td class="plan-ligne-ordre text-muted"></td>
                <td><input type="text" class="form-control form-control-sm plan-ligne-libelle" maxlength="100" placeholder="Ex : 1re tranche"></td>
                <td><input type="date" class="form-control form-control-sm plan-ligne-date"></td>
                <td><input type="number" class="form-control form-control-sm plan-ligne-part" min="0" max="100" step="0.01"></td>
                <td><button type="button" class="btn btn-sm btn-light plan-ligne-retirer" title="Retirer"><i class="fas fa-times text-danger"></i></button></td>
            </tr>`);
        $tr.find('.plan-ligne-libelle').val(ligne.libelle ?? '');
        $tr.find('.plan-ligne-date').val((ligne.date_echeance ?? '').substring(0, 10));
        $tr.find('.plan-ligne-part').val(ligne.pourcentage ?? '');
        $lignes.append($tr);
        renumeroter();
    }

    // Les champs sont nommés au dernier moment : l'ordre des lignes peut
    // changer tant que la modale est ouverte (ajout, retrait).
    function renumeroter() {
        let total = 0;
        $lignes.find('tr').each(function (i) {
            const $tr = $(this);
            $tr.find('.plan-ligne-ordre').text(i + 1);
            $tr.find('.plan-ligne-libelle').attr('name', `lignes[${i}][libelle]`);
            $tr.find('.plan-ligne-date').attr('name', `lignes[${i}][date_echeance]`);
            $tr.find('.plan-ligne-part').attr('name', `lignes[${i}][pourcentage]`);
            total += parseFloat($tr.find('.plan-ligne-part').val() || 0);
        });
        total = Math.round(total * 100) / 100;
        $total.text(total.toLocaleString('fr-FR') + ' %')
              .toggleClass('text-danger', Math.abs(total - 100) > 0.01)
              .toggleClass('text-success', Math.abs(total - 100) <= 0.01);
    }

    function lectureSeule(oui) {
        $form.find('input:not([type=hidden]), textarea').prop('readonly', oui);
        $('#plan-ajouter-ligne, #plan-enregistrer').toggle(!oui);
        $lignes.find('.plan-ligne-retirer').toggle(!oui);
    }

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $method.val('POST');
        $nomError.text('');
        $lignesError.text('');
        $nom.removeClass('is-invalid');
        $lignes.empty();
        lectureSeule(false);
        renumeroter();
    }

    $('#plan-ajouter-ligne').on('click', () => ajouterLigne());
    $(document).on('click', '.plan-ligne-retirer', function () { $(this).closest('tr').remove(); renumeroter(); });
    $(document).on('input', '.plan-ligne-part', renumeroter);

    // Conversion des tranches de l'année de l'en-tête : confirmation, puis bilan
    $('#btn-convertir-tranches').on('click', function () {
        const $btn = $(this);
        Swal.fire({
            title: 'Convertir les tranches ?',
            html: "Les tranches des frais de l'année <strong>{{ $annee?->libelle }}</strong> deviennent des plans d'échéancier, avec leurs dates et leurs montants. "
                + "Les tranches d'origine sont conservées et les paiements ne sont pas modifiés.",
            icon: 'question', showCancelButton: true, confirmButtonColor: '#E31E24',
            confirmButtonText: 'Oui, convertir', cancelButtonText: 'Annuler',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;
            $btn.prop('disabled', true);
            $.ajax({
                url: '{{ route('finances.plan-echeanciers.conversion.executer') }}', method: 'POST',
                data: { _token: '{{ csrf_token() }}' }, headers: { 'Accept': 'application/json' },
            }).done(function (res) {
                const b = res.data?.bilan || {};
                window.showToastThenReload(
                    (b.plans_crees ?? 0) + ' plan(s) créé(s), ' + (b.echeances_creees ?? 0) + ' échéance(s) reprise(s)'
                    + ((b.incoherences || []).length ? ' — ' + b.incoherences.length + ' cas à vérifier' : '') + '.', 'success');
            }).fail(function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                $btn.prop('disabled', false);
            });
        });
    });

    $('#btnNouveauPlan').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Nouveau plan');
        ajouterLigne();
    });

    $(document).on('click', '.btn-edit-plan', function () {
        resetForm();
        currentId = $(this).data('id');
        const consultation = String($(this).data('lecture')) === '1';
        $title.html(consultation
            ? '<i class="fas fa-eye me-2"></i>Échéances du plan'
            : '<i class="fas fa-pen me-2"></i>Modifier le plan');
        $method.val('PUT');

        $.getJSON(`{{ url('finances/plan-echeanciers') }}/${currentId}`, function (data) {
            $nom.val(data.plan.nom);
            $('#plan-description').val(data.plan.description);
            (data.plan.lignes || [])
                .filter(l => parseInt(l.etat ?? 1, 10) === 1)
                .sort((a, b) => (a.ordre ?? 0) - (b.ordre ?? 0))
                .forEach(ajouterLigne);
            lectureSeule(consultation);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        $nomError.text('');
        $lignesError.text('');
        $nom.removeClass('is-invalid');
        renumeroter();

        const url = currentId ? `{{ url('finances/plan-echeanciers') }}/${currentId}` : storeUrl;

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                const erreurs = xhr.responseJSON?.errors;
                if (xhr.status === 422 && erreurs) {
                    if (erreurs.nom) {
                        $nom.addClass('is-invalid');
                        $nomError.text(erreurs.nom[0]);
                    }
                    const autres = Object.keys(erreurs).filter(champ => champ !== 'nom');
                    if (autres.length) {
                        $lignesError.text(erreurs[autres[0]][0]);
                    }
                } else if (xhr.status === 422 && xhr.responseJSON?.message) {
                    // Règle de gestion refusée par le service (total ≠ 100 %, etc.)
                    $lignesError.text(xhr.responseJSON.message);
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush