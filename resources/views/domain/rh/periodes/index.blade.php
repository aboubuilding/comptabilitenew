@extends('admin.layouts.app')

@section('title', 'Périodes de paie')
@section('page_icon', 'fa-calendar-alt')
@section('page_title', 'Périodes de paie')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item"><a href="{{ route('rh.paies.index') }}">Paie</a></li>
    <li class="breadcrumb-item active">Périodes</li>
@endsection

@section('page_actions')
    <a href="{{ route('rh.paies.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-money-check-alt me-1"></i> Bulletins
    </a>
    <a href="{{ route('rh.paies.dashboard') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-chart-pie me-1"></i> Dashboard
    </a>
    @can('paie.gerer')
        <button type="button" class="btn btn-nouveau" data-bs-toggle="modal" data-bs-target="#periodeModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle période
        </button>
    @endcan
@endsection

@section('css')
<style>
    .main-card {
        border:none; border-radius:16px;
        box-shadow: 0 10px 30px rgba(122,15,28,.06);
        background:#fff; overflow:hidden;
    }
    .main-card .card-header { border-bottom:1px solid #ece4d6 !important; }

    .table-periodes thead tr { background:#faf6ee; }
    .table-periodes thead th {
        color:var(--school-red); font-size:.76rem; font-weight:700;
        text-transform:uppercase; padding:.9rem 1rem;
        border-bottom:2px solid #ece4d6; white-space:nowrap;
    }
    .table-periodes thead th i { color:var(--school-gold-dark); }
    .table-periodes tbody td {
        padding:.9rem 1rem; border-bottom:1px solid #ece4d6;
        font-size:.9rem; vertical-align:middle;
    }
    .table-periodes tbody tr:hover { background:#fdfaf5; }

    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .btn-filtrer {
        background:var(--school-red); color:#fff; border:none;
        border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem;
    }
    .btn-nouveau {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600;
    }
    .btn-nouveau:hover { color:#fff; }
    .filtre-input {
        border-radius:10px; border:1px solid #ece4d6;
        padding:.55rem .9rem; font-size:.88rem;
    }
    .btn-outline-scolarite {
        border:1px solid #ece4d6; color:var(--school-ink);
        background:#fff; font-size:.82rem;
    }
    .btn-outline-scolarite:hover {
        background:#faf6ee; border-color:var(--school-red); color:var(--school-red);
    }

    /* Boutons d'action par ligne */
    .btn-action-periode {
        background:#2563eb; color:#fff; border:none; border-radius:8px;
        font-size:.78rem; padding:.35rem .7rem;
        transition: background .15s;
    }
    .btn-action-periode:hover:not(:disabled) { background:#1d4ed8; color:#fff; }
    .btn-action-periode:disabled { opacity:.6; cursor:not-allowed; }
    .btn-action-pdf {
        background:#C81E3A; color:#fff; border:none; border-radius:8px;
        font-size:.78rem; padding:.35rem .7rem;
        transition: background .15s;
    }
    .btn-action-pdf:hover { color:#fff; background:#a3182f; }

    /* Badge période vide */
    .badge-vide {
        background:#eceff2; color:#5b6b7a;
        padding:.2em .7em; border-radius:999px;
        font-size:.72rem; font-weight:600;
    }

    /* Petit indicateur "en cours" dans la cellule */
    .gen-status {
        display:inline-block; margin-left:.5rem;
        font-size:.72rem; color:var(--school-muted);
    }
    .gen-status .spinner-border {
        width:.75rem; height:.75rem;
        border-width:.12em;
        vertical-align:-.05em;
    }
</style>
@endsection

@section('contenu')
<div class="container-fluid p-0">
    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-periodes align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-tag me-2"></i>Libellé</th>
                            <th><i class="fas fa-calendar-day me-2"></i>Début</th>
                            <th><i class="fas fa-calendar-check me-2"></i>Fin</th>
                            <th class="text-center"><i class="fas fa-file-invoice me-2"></i>Bulletins</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Masse nette</th>
                            <th class="text-center pe-4" style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($periodes as $periode)
                        @php
                            $nbBulletins = $periode->paies_count ?? 0;
                        @endphp
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $periode->libelle }}</td>
                            <td class="text-sm">
                                {{ optional($periode->date_debut)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="text-sm">
                                {{ optional($periode->date_fin)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="text-center">
                                @if ($nbBulletins > 0)
                                    <a href="{{ route('rh.paies.index', ['periode_id' => $periode->id]) }}"
                                       class="text-decoration-none fw-semibold"
                                       style="color:var(--school-red);">
                                        {{ $nbBulletins }}
                                    </a>
                                @else
                                    <span class="badge-vide">Aucun</span>
                                @endif
                            </td>
                            <td class="num grand">
                                {{ number_format($periode->masse_nette, 0, ',', ' ') }}
                            </td>
                            <td class="text-center pe-4">
                                @can('paie.gerer')
                                    <button type="button"
                                            class="btn btn-action-periode btn-generer"
                                            data-periode="{{ $periode->id }}"
                                            data-libelle="{{ $periode->libelle }}">
                                        <i class="fas fa-calculator me-1"></i> Générer
                                    </button>
                                    <span class="gen-status" id="gen-status-{{ $periode->id }}"></span>
                                @endcan

                                <a href="{{ route('rh.paies.exportPeriodePdf', $periode) }}"
                                   class="btn btn-action-pdf ms-1 {{ $nbBulletins === 0 ? 'disabled' : '' }}"
                                   @if ($nbBulletins === 0) title="Aucun bulletin à exporter" @endif>
                                    <i class="fas fa-file-pdf me-1"></i> PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-alt"></i>
                                    <p class="mb-0">Aucune période enregistrée.</p>
                                    @can('paie.gerer')
                                        <p class="text-muted small mb-0">
                                            Cliquez sur « Nouvelle période » pour en créer une.
                                        </p>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modale création période --}}
@can('paie.gerer')
<div class="modal fade" id="periodeModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
            <form method="POST" action="{{ route('rh.periodes.store') }}">
                @csrf
                <div class="modal-header"
                     style="background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));color:#fff;">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus me-2"></i>Nouvelle période
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Libellé <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="libelle" class="form-control"
                               required maxlength="100"
                               placeholder="Ex : Juin 2026">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                Début <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="date_debut" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                Fin <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="date_fin" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn"
                            style="background:var(--school-red);color:#fff;border:none;border-radius:8px;font-weight:600;padding:.55rem 1.4rem;">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('js')
<script>
$(function () {
    // Utilitaires
    function formatMoney(v) {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(v) + ' F';
    }

    // Marqueurs pour empêcher les doubles clics / doubles jobs
    const generationEnCours = {};

    /**
     * Lance la génération d'une période (via job queue) et poll le statut
     * jusqu'à ce que le worker ait terminé.
     */
    function genererPeriode($btn) {
        const periodeId = $btn.data('periode');
        const libelle   = $btn.data('libelle');

        // Empêche le double clic (déjà en cours localement)
        if (generationEnCours[periodeId]) {
            window.showToast('Une génération est déjà en cours pour cette période.', 'error');
            return;
        }

        const $status = $('#gen-status-' + periodeId);

        // Confirmation avec la modale SweetAlert globale
        Swal.fire({
            title: 'Générer les bulletins ?',
            html: `Créer les bulletins manquants pour la période <strong>${libelle}</strong> ?<br>
                   <small class="text-muted">Les employés ayant déjà un bulletin seront ignorés.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6f7e8c',
            confirmButtonText: 'Oui, générer',
            cancelButtonText: 'Annuler',
        }).then(function (result) {
            if (! result.isConfirmed) return;

            // Marque localement
            generationEnCours[periodeId] = true;

            $btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin me-1"></i> Lancement...');

            $status.html('');

            $.ajax({
                url: `{{ url('rh/periodes') }}/${periodeId}/generer`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                headers: { 'Accept': 'application/json' },
                success: function () {
                    window.showToast('Génération lancée. Suivi en cours...', 'success');
                    $btn.html('<i class="fas fa-spinner fa-spin me-1"></i> En cours...');
                    $status.html('<span class="spinner-border text-primary" role="status"></span>');

                    // Polling avec backoff progressif (2s → 3s → 4s → 5s max)
                    let tentatives = 0;
                    const maxTentatives = 120; // ~6-8 min selon backoff

                    function poll() {
                        tentatives++;

                        $.get(`{{ url('rh/periodes') }}/${periodeId}/statut-generation`, function (res) {
                            if (res.statut === 'termine') {
                                // Succès
                                generationEnCours[periodeId] = false;
                                $btn.prop('disabled', false)
                                    .html('<i class="fas fa-calculator me-1"></i> Générer');
                                $status.html('');

                                if (res.recap) {
                                    const r = res.recap;
                                    const msg = `${r.crees} bulletin(s) créé(s), ${r.ignores} ignoré(s). `
                                        + `Brut : ${formatMoney(r.total_brut)} — Net : ${formatMoney(r.total_net)}. `
                                        + `Billetterie : ${r.repartition.billette.nb} (${formatMoney(r.repartition.billette.montant)}) · `
                                        + `Banque : ${r.repartition.banque.nb} (${formatMoney(r.repartition.banque.montant)}).`;
                                    window.showToast(msg, 'success');
                                } else {
                                    window.showToast('Génération terminée.', 'success');
                                }

                                // Recharge après un court délai pour voir les compteurs actualisés
                                setTimeout(() => window.location.reload(), 1500);
                                return;
                            }

                            if (res.statut === 'erreur') {
                                // Erreur serveur
                                generationEnCours[periodeId] = false;
                                $btn.prop('disabled', false)
                                    .html('<i class="fas fa-calculator me-1"></i> Générer');
                                $status.html('');
                                window.showToast('Erreur : ' + (res.erreur || 'Inconnue'), 'error');
                                return;
                            }

                            // Toujours en cours → on continue avec backoff
                            if (tentatives >= maxTentatives) {
                                generationEnCours[periodeId] = false;
                                $btn.prop('disabled', false)
                                    .html('<i class="fas fa-calculator me-1"></i> Générer');
                                $status.html('');
                                window.showToast('Le suivi a expiré. Vérifiez les logs.', 'error');
                                return;
                            }

                            // Backoff : 2s pour les 10 premières tentatives, puis +1s jusqu'à 5s
                            const delay = Math.min(2000 + Math.floor(tentatives / 10) * 500, 5000);
                            setTimeout(poll, delay);

                        }).fail(function () {
                            generationEnCours[periodeId] = false;
                            $btn.prop('disabled', false)
                                .html('<i class="fas fa-calculator me-1"></i> Générer');
                            $status.html('');
                            window.showToast('Erreur de communication avec le serveur.', 'error');
                        });
                    }

                    // Premier appel après 2s (laisse le temps au job de démarrer)
                    setTimeout(poll, 2000);
                },
                error: function (xhr) {
                    generationEnCours[periodeId] = false;
                    $btn.prop('disabled', false)
                        .html('<i class="fas fa-calculator me-1"></i> Générer');
                    $status.html('');
                    window.showToast(
                        xhr.responseJSON?.message || 'Erreur lors du lancement.',
                        'error'
                    );
                },
            });
        });
    }

    // Délégation : clique sur n'importe quel bouton "Générer"
    $(document).on('click', '.btn-generer', function () {
        genererPeriode($(this));
    });
});
</script>
@endpush