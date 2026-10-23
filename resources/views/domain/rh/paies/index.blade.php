@extends('admin.layouts.app')

@section('title', 'Bulletins de paie')
@section('page_icon', 'fa-money-check-alt')
@section('page_title', 'Paie & Rémunérations')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item active">Paie</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('rh.paies.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('rh.paies.dashboard', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-chart-pie me-1"></i> Dashboard
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('rh.periodes.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-calendar-alt me-1"></i> Périodes
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('rh.remunerations.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-sliders-h me-1"></i> Rémunérations
    </x-bouton>
    @can('paie.gerer')
        <x-bouton variante="nouveau" type="button" data-bs-toggle="modal" data-bs-target="#paieModal">
            <i class="fas fa-plus-circle me-1"></i> Générer un bulletin
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .paie-page { --p-bg-soft:var(--eim-cream); --p-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--p-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--p-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--p-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--p-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--p-bg-soft); border-color:var(--school-red); color:var(--school-red); }
    .compteur-badge { display:inline-block; background:var(--p-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--p-border); }

    .table-paies thead tr { background:var(--p-bg-soft); }
    .table-paies thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--p-border); white-space:nowrap; }
    .table-paies thead th i { color:var(--school-gold-dark); }
    .table-paies tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--p-border); font-size:.9rem; vertical-align:middle; }
    .table-paies tbody tr:hover { background:var(--eim-hover); }

    .reference { font-family:monospace; font-size:.75rem; background:var(--p-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }
    .num.net { color:var(--eim-success); font-weight:800; font-size:1rem; }

    .badge-statut.brouillon { background:#eceff2; color:var(--eim-ink-soft); }
    .badge-statut.valide    { background:#e0ecfc; color:#2563eb; }
    .badge-statut.annule    { background:#fdecea; color:var(--school-red); }

    /* Badges mode paiement */
    .badge-mode {
        display:inline-flex; align-items:center; gap:5px;
        padding:.3em .75em; border-radius:999px;
        font-size:.7rem; font-weight:700;
    }
    .badge-mode.banque   { background:#e0ecfc; color:#2563eb; }
    .badge-mode.billette { background:#fdf4e0; color:var(--school-gold-dark); }

    /* Badges retrait */
    .badge-retrait {
        display:inline-flex; align-items:center; gap:5px;
        padding:.3em .75em; border-radius:999px;
        font-size:.7rem; font-weight:700;
    }
    .badge-retrait.attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-retrait.retire  { background:#e3f7ec; color:var(--eim-success); }
    .badge-retrait.vire    { background:#e0ecfc; color:#2563eb; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-rh { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-rh { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--p-bg-soft); border:1px solid var(--p-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--p-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--p-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="paie-page">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-file-invoice" couleur="blue" libelle="Bulletins">{{ $stats['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="gold" libelle="Masse brute">{{ number_format($stats['masse_brute'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-money-bill-wave" couleur="green" libelle="Masse nette">{{ number_format($stats['masse_nette'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-hourglass-half" couleur="red" libelle="En brouillon">{{ $stats['brouillons'] }}</x-stat>
        </div>
    </div>

    {{-- Tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Employé..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="periode_id" class="form-select filtre-input">
                        <option value="">— Période —</option>
                        @foreach ($periodes as $p)
                            <option value="{{ $p->id }}" @selected(($filters['periode_id'] ?? '') == $p->id)>
                                {{ $p->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </x-bouton>
                    <x-bouton variante="contour" href="{{ route('rh.paies.index') }}">
                        <i class="fas fa-undo"></i>
                    </x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-paies align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Référence</th>
                            <th>Employé</th>
                            <th>Période</th>
                            <th class="text-end">Brut</th>
                            <th class="text-end">Déductions</th>
                            <th class="text-end">Retenues</th>
                            <th class="text-end">Net à payer</th>
                            <th>Statut</th>
                            <th>Mode / Retrait</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($paies as $paie)
                        <tr>
                            <td class="ps-4">
                                <span class="reference">{{ $paie->reference ?? ('#' . $paie->id) }}</span>
                            </td>
                            <td class="text-sm">
                                <a href="{{ route('rh.employes.show', $paie->personnel_id) }}"
                                   class="text-decoration-none">
                                    <div class="fw-semibold">{{ $paie->employe?->nom_prenom }}</div>
                                    <div class="text-xs text-muted">{{ $paie->employe?->profession?->libelle }}</div>
                                </a>
                            </td>
                            <td class="text-sm">{{ $paie->periode?->libelle ?? '—' }}</td>
                            <td class="num">{{ number_format((float) $paie->total_brut, 0, ',', ' ') }}</td>
                            <td class="num">{{ number_format((float) $paie->total_deductions, 0, ',', ' ') }}</td>
                            <td class="num">{{ number_format((float) $paie->total_retenues, 0, ',', ' ') }}</td>
                            <td class="num net">{{ number_format((float) $paie->net_a_payer, 0, ',', ' ') }}</td>
                            <td>
                                <x-badge class="{{ $paie->statut?->css() }}" point>{{ $paie->statut?->label() }}
                                </x-badge>
                            </td>
                            <td>
                                {{-- Mode de paiement --}}
                                @if ($paie->isPayeParBanque())
                                    <span class="badge-mode banque">
                                        <i class="fas fa-university"></i> Banque
                                    </span>
                                @else
                                    <span class="badge-mode billette">
                                        <i class="fas fa-money-bill-wave"></i> Billetterie
                                    </span>
                                @endif

                                {{-- État du retrait (billetterie) --}}
                                @if ($paie->isPaye() && $paie->isPayeABilletterie())
                                    @if ($paie->retire_a_la_billetterie)
                                        <div class="mt-1">
                                            <span class="badge-retrait retire">
                                                <i class="fas fa-check"></i> Retiré le
                                                {{ optional($paie->date_retrait)->format('d/m/Y') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="mt-1">
                                            <span class="badge-retrait attente">
                                                <i class="fas fa-hourglass-half"></i>
                                                En attente ({{ $paie->jours_depuis_paiement }} j)
                                            </span>
                                        </div>
                                    @endif
                                @elseif ($paie->isPaye() && $paie->isPayeParBanque())
                                    <div class="mt-1">
                                        <span class="badge-retrait vire">
                                            <i class="fas fa-check"></i> Virement effectué
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>

                                        {{-- Toujours disponible : voir le bulletin --}}
                                        <li>
                                            <a class="dropdown-item" href="{{ route('rh.paies.show', $paie) }}">
                                                <i class="fas fa-eye"></i> Voir le bulletin
                                            </a>
                                        </li>

                                        {{-- Toujours disponible : PDF du bulletin --}}
                                        <li>
                                            <a class="dropdown-item" href="{{ route('rh.paies.exportPdf', $paie) }}">
                                                <i class="fas fa-file-pdf"></i> Télécharger PDF
                                            </a>
                                        </li>

                                        @can('paie.gerer')
                                            {{-- Valider (brouillon uniquement) --}}
                                            @if ($paie->isBrouillon())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('rh.paies.valider', $paie) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Valider ce bulletin ?">
                                                        @csrf
                                                        <button class="dropdown-item">
                                                            <i class="fas fa-check"></i> Valider
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        @endcan

                                        {{-- Le règlement des bulletins revient au Comptable (cahier des charges 5.2) --}}
                                        @can('paie.payer')
                                            {{-- Payer (brouillon ou validé) --}}
                                            @if (! $paie->isPaye() && $paie->statut?->value !== 'annule')
                                                <li>
                                                    <form action="{{ route('rh.paies.payer', $paie) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Marquer comme payé ?">
                                                        @csrf
                                                        <button class="dropdown-item">
                                                            <i class="fas fa-money-bill-wave"></i> Marquer payé
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif

                                            {{-- Confirmer retrait billetterie (uniquement si payé + billetterie + non retiré) --}}
                                            @if ($paie->isEnAttenteRetrait())
                                                <li>
                                                    <form action="{{ route('rh.paies.retrait', $paie) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Confirmer le retrait en espèces ?"
                                                          data-confirm-text="L'employé a bien récupéré sa paie à la billetterie.">
                                                        @csrf
                                                        <button class="dropdown-item" style="color:var(--eim-success);">
                                                            <i class="fas fa-hand-holding-usd"></i>
                                                            Confirmer le retrait
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        @endcan

                                        @can('paie.gerer')
                                            {{-- Annuler (brouillon ou validé uniquement) --}}
                                            @if (! $paie->statut?->isVerrouille())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                            class="dropdown-item text-danger btn-annuler-paie"
                                                            data-id="{{ $paie->id }}"
                                                            data-reference="{{ $paie->reference }}"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#annulerPaieModal">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </button>
                                                </li>
                                            @endif
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="10" icone="fas fa-money-check-alt" :paragraphe="false"><p class="mb-0">Aucun bulletin de paie.</p></x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$paies->appends(request()->query())" /></div>

    {{-- Modale génération bulletin --}}
    @can('paie.gerer')
    <x-modale id="paieModal" data-bs-backdrop="static" contenu-class="modal-rh" entete-class="modal-header-rh" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="paie-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-file-invoice me-2"></i>Générer un bulletin</x-slot:titre>
        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
            <i class="fas fa-info-circle me-1"></i>
            Le bulletin est calculé automatiquement depuis la rémunération, les déductions
            et les retenues de l'employé (IRPP calculé selon son mode). Il est créé en
            <strong>brouillon</strong>.
        </div>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Employé</x-libelle>
                <select name="employe_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($employes as $e)
                        <option value="{{ $e->id }}">{{ $e->nom_prenom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-libelle requis>Période</x-libelle>
                <select name="periode_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($periodes as $p)
                        <option value="{{ $p->id }}">{{ $p->libelle }}</option>
                    @endforeach
                </select>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">
                <i class="fas fa-calculator me-1"></i> Générer
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modale annulation --}}
    @can('paie.gerer')
    <x-modale id="annulerPaieModal" aria-hidden="true" contenu-class="modal-rh" entete-style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="annuler-paie-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-exclamation-triangle me-2"></i>Annuler le bulletin</x-slot:titre>
        <p>Annuler le bulletin <strong id="annuler-ref">—</strong> ?</p>
        <x-section>
            <x-libelle class="small fw-semibold" requis>Motif</x-libelle>
            <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer</button>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    // ============ FORMULAIRE GÉNÉRATION ============
    $('#paie-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("rh.paies.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#paieModal').modal('hide');
                window.showToastThenReload(res.message || 'Bulletin généré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            }
        });
    });

    // ============ FORMULAIRE ANNULATION ============
    $('.btn-annuler-paie').on('click', function () {
        const id = $(this).data('id');
        const ref = $(this).data('reference') ?? ('#' + id);
        $('#annuler-ref').text(ref);
        $('#annuler-paie-form').attr('action', `{{ url('rh/paies') }}/${id}/annuler`);
    });

    $('#annuler-paie-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#annulerPaieModal').modal('hide');
                window.showToastThenReload(res.message || 'Bulletin annulé.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });
});
</script>
@endpush