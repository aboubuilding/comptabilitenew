@extends('admin.layouts.app')

@section('title', 'Bulletin — ' . ($paie->reference ?? $paie->id))
@section('page_icon', 'fa-file-invoice-dollar')
@section('page_title', 'Bulletin de paie')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item"><a href="{{ route('rh.paies.index') }}">Paie</a></li>
    <li class="breadcrumb-item active">{{ $paie->reference ?? $paie->id }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('rh.paies.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
    <button type="button" onclick="window.print()" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-print me-1"></i> Imprimer
    </button>
    <a href="{{ route('rh.paies.exportPdf', $paie) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-pdf me-1"></i> PDF
    </a>
@endsection

@section('css')
<style>
    .bulletin-card {
        background:#fff; border-radius:16px;
        box-shadow: 0 10px 30px rgba(122,15,28,.06);
        overflow:hidden; margin-bottom:1rem;
    }
    .bulletin-header {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; padding:1.5rem 2rem;
    }
    .bulletin-header h2 { margin:0; font-size:1.2rem; font-weight:700; letter-spacing:1px; }
    .bulletin-header .sub { font-size:.8rem; opacity:.85; margin-top:4px; }

    .bulletin-body { padding:1.5rem 2rem; }

    .bulletin-row { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #f1ece2; }
    .bulletin-row:last-child { border-bottom:none; }
    .bulletin-row .label { font-size:.85rem; color:var(--school-muted); }
    .bulletin-row .value { font-size:.9rem; font-weight:600; color:var(--school-ink); font-variant-numeric:tabular-nums; }
    .bulletin-row.section .label,
    .bulletin-row.section .value { font-weight:700; color:var(--school-red-dark); text-transform:uppercase; font-size:.78rem; letter-spacing:.4px; }
    .bulletin-row.total .label,
    .bulletin-row.total .value { font-size:1.05rem; font-weight:800; color:var(--school-red-dark); padding-top:.75rem; border-top:2px solid var(--school-red); margin-top:.5rem; }
    .bulletin-row.net .label,
    .bulletin-row.net .value { font-size:1.25rem; font-weight:800; color:#1c7a4d; padding-top:.75rem; border-top:2px solid #1c7a4d; margin-top:.5rem; }

    .badge-statut {
        display:inline-flex; align-items:center; gap:6px;
        padding:.35em .85em; border-radius:999px;
        font-size:.72rem; font-weight:600;
        background:rgba(255,255,255,.18); color:#fff;
    }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    /* Bloc mode de paiement */
    .mode-block {
        background:#f0f6ff; border-left:3px solid #2563eb;
        border-radius:6px; padding:.85rem 1rem;
        font-size:.85rem; color:#1e3a8a;
        margin-top:1rem;
    }
    .mode-block .label { color:#5a7ac7; font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; font-weight:600; }
    .mode-block .value { font-weight:600; color:#1e3a8a; }

    /* Bloc retrait billetterie */
    .retrait-block {
        background:#fdf4e0; border-left:3px solid var(--school-gold-dark);
        border-radius:6px; padding:.85rem 1rem;
        font-size:.85rem; color:#7a5a00;
        margin-top:1rem;
    }
    .retrait-block.ok {
        background:#e3f7ec; border-left-color:#1c7a4d; color:#1c7a4d;
    }

    /* Bloc observations */
    .observations-block {
        background:#faf6ee; border-radius:8px;
        padding:.85rem 1rem; margin-top:1rem;
        font-size:.85rem; color:#4a5560;
    }

    @media print {
        .page-header-bar, #app-footer, .hbtp-root, #toast-container,
        .btn, .btn-outline-scolarite, .sidebar-actions,
        .sidebar-etat { display:none !important; }
        .content-area { padding:0 !important; }
        .bulletin-card { box-shadow:none; border:1px solid #ddd; }
        .col-lg-4 { display:none !important; }
        .col-lg-8 { flex:0 0 100% !important; max-width:100% !important; }
    }
</style>
@endsection

@section('contenu')
<div class="row g-3">

    {{-- ============ BULLETIN ============ --}}
    <div class="col-lg-8">
        <div class="bulletin-card">
            <div class="bulletin-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2>BULLETIN DE PAIE</h2>
                        <div class="sub">
                            Référence : {{ $paie->reference ?? '#' . $paie->id }}
                        </div>
                    </div>
                    <span class="badge-statut">
                        <span class="badge-dot"></span>{{ $paie->statut?->label() }}
                    </span>
                </div>
            </div>

            <div class="bulletin-body">

                {{-- Employé + période --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="text-xs text-muted text-uppercase mb-1">Employé</div>
                        <div class="fw-bold">{{ $paie->employe?->nom_prenom ?? '—' }}</div>
                        <div class="text-sm text-muted">{{ $paie->employe?->profession?->libelle ?? '—' }}</div>
                        @if ($paie->employe?->numero_cnss)
                            <div class="text-xs text-muted">CNSS : {{ $paie->employe->numero_cnss }}</div>
                        @endif
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="text-xs text-muted text-uppercase mb-1">Période</div>
                        <div class="fw-bold">{{ $paie->periode?->libelle ?? '—' }}</div>
                        <div class="text-sm text-muted">
                            {{ optional($paie->periode?->date_debut)->format('d/m/Y') ?? '—' }}
                            →
                            {{ optional($paie->periode?->date_fin)->format('d/m/Y') ?? '—' }}
                        </div>
                    </div>
                </div>

                {{-- Éléments de rémunération --}}
                <div class="bulletin-row section">
                    <span class="label">Éléments de rémunération</span>
                    <span class="value">Montant</span>
                </div>
                <div class="bulletin-row">
                    <span class="label">Salaire de base</span>
                    <span class="value">{{ number_format((float) $paie->salaire_base, 2, ',', ' ') }}</span>
                </div>
                @if ($paie->prime_anciennete)
                    <div class="bulletin-row">
                        <span class="label">Prime d'ancienneté</span>
                        <span class="value">{{ number_format((float) $paie->prime_anciennete, 2, ',', ' ') }}</span>
                    </div>
                @endif
                @if ($paie->sursalaire)
                    <div class="bulletin-row">
                        <span class="label">Sursalaire</span>
                        <span class="value">{{ number_format((float) $paie->sursalaire, 2, ',', ' ') }}</span>
                    </div>
                @endif
                @if ($paie->bonus)
                    <div class="bulletin-row">
                        <span class="label">Bonus</span>
                        <span class="value">{{ number_format((float) $paie->bonus, 2, ',', ' ') }}</span>
                    </div>
                @endif
                @if ($paie->logement)
                    <div class="bulletin-row">
                        <span class="label">Indemnité de logement</span>
                        <span class="value">{{ number_format((float) $paie->logement, 2, ',', ' ') }}</span>
                    </div>
                @endif
                @if ($paie->nourriture)
                    <div class="bulletin-row">
                        <span class="label">Indemnité de nourriture</span>
                        <span class="value">{{ number_format((float) $paie->nourriture, 2, ',', ' ') }}</span>
                    </div>
                @endif
                @if ($paie->deplacement)
                    <div class="bulletin-row">
                        <span class="label">Indemnité de déplacement</span>
                        <span class="value">{{ number_format((float) $paie->deplacement, 2, ',', ' ') }}</span>
                    </div>
                @endif
                @if ($paie->autre_avantage)
                    <div class="bulletin-row">
                        <span class="label">Autres avantages</span>
                        <span class="value">{{ number_format((float) $paie->autre_avantage, 2, ',', ' ') }}</span>
                    </div>
                @endif

                {{-- Total brut --}}
                <div class="bulletin-row total">
                    <span class="label">Total brut</span>
                    <span class="value">{{ number_format((float) $paie->total_brut, 2, ',', ' ') }}</span>
                </div>

                {{-- Déductions --}}
                @if ($paie->total_deductions > 0)
                    <div class="bulletin-row section mt-3">
                        <span class="label">Déductions</span>
                        <span class="value">Montant</span>
                    </div>
                    <div class="bulletin-row">
                        <span class="label">CNSS, charges familiales, forfait professionnel</span>
                        <span class="value">− {{ number_format((float) $paie->total_deductions, 2, ',', ' ') }}</span>
                    </div>
                @endif

                {{-- Retenues --}}
                @if ($paie->total_retenues > 0)
                    <div class="bulletin-row section mt-3">
                        <span class="label">Retenues</span>
                        <span class="value">Montant</span>
                    </div>
                    <div class="bulletin-row">
                        <span class="label">
                            IRPP
                            @if ($paie->employe?->irpp_mode === 'auto')
                                <span class="text-xs text-muted"> (calculé par barème)</span>
                            @elseif ($paie->employe?->irpp_mode === 'manuel')
                                <span class="text-xs text-muted"> (montant fixe)</span>
                            @endif
                        </span>
                        <span class="value">− {{ number_format((float) $paie->total_retenues, 2, ',', ' ') }}</span>
                    </div>
                @endif

                {{-- Net à payer --}}
                <div class="bulletin-row net">
                    <span class="label">NET À PAYER</span>
                    <span class="value">{{ number_format((float) $paie->net_a_payer, 2, ',', ' ') }} F</span>
                </div>

                {{-- Mode de paiement --}}
                @if ($paie->mode_paiement)
                    <div class="mode-block">
                        <div class="label">Mode de paiement</div>
                        <div class="value">
                            @if ($paie->isPayeParBanque())
                                <i class="fas fa-university me-1"></i>Virement bancaire
                                @if ($paie->banque) — {{ $paie->banque }} @endif
                                @if ($paie->iban) · IBAN : {{ $paie->iban }} @endif
                            @else
                                <i class="fas fa-money-bill-wave me-1"></i>Paiement à la billetterie (espèces)
                            @endif
                        </div>
                    </div>
                @endif

                {{-- État du retrait billetterie --}}
                @if ($paie->isPaye() && $paie->isPayeABilletterie())
                    @if ($paie->retire_a_la_billetterie)
                        <div class="retrait-block ok">
                            <i class="fas fa-check-circle me-1"></i>
                            <strong>Espèces retirées</strong>
                            le {{ optional($paie->date_retrait)->format('d/m/Y') }}
                            @if ($paie->retireur) par {{ $paie->retireur->name }} @endif
                        </div>
                    @else
                        <div class="retrait-block">
                            <i class="fas fa-hourglass-half me-1"></i>
                            <strong>En attente de retrait</strong>
                            — payé le {{ optional($paie->date_paiement)->format('d/m/Y') }}
                            ({{ $paie->jours_depuis_paiement }} jour(s) écoulé(s))
                        </div>
                    @endif
                @endif

                {{-- Observations --}}
                @if ($paie->observations)
                    <div class="observations-block">
                        <strong>Observations :</strong> {{ $paie->observations }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============ SIDEBAR ============ --}}
    <div class="col-lg-4">

        {{-- État du bulletin --}}
        <div class="bulletin-card sidebar-etat">
            <div class="bulletin-body">
                <h6 class="fw-bold mb-3">État du bulletin</h6>

                <div class="bulletin-row">
                    <span class="label">Créé le</span>
                    <span class="value">{{ optional($paie->created_at)->format('d/m/Y H:i') ?? '—' }}</span>
                </div>

                @if ($paie->isPaye())
                    <div class="bulletin-row">
                        <span class="label">Payé le</span>
                        <span class="value">{{ optional($paie->date_paiement)->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="bulletin-row">
                        <span class="label">Payé par</span>
                        <span class="value">{{ $paie->payeur?->name ?? '—' }}</span>
                    </div>
                @endif

                @if ($paie->retire_a_la_billetterie)
                    <div class="bulletin-row">
                        <span class="label">Retiré le</span>
                        <span class="value">{{ optional($paie->date_retrait)->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="bulletin-row">
                        <span class="label">Retiré par</span>
                        <span class="value">{{ $paie->retireur?->name ?? '—' }}</span>
                    </div>
                @endif

                <div class="bulletin-row">
                    <span class="label">Statut</span>
                    <span class="value">
                        <span class="badge-statut" style="background:var(--school-red);color:#fff;">
                            {{ $paie->statut?->label() }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        @canany(['paie.gerer', 'paie.payer'])
            <div class="sidebar-actions">
                @if ($paie->isBrouillon() && auth()->user()->can('paie.gerer'))
                    <form action="{{ route('rh.paies.valider', $paie) }}" method="POST" class="d-grid mb-2 form-confirm-delete"
                          data-confirm-title="Valider ce bulletin ?"
                          data-confirm-text="Une fois validé, le bulletin pourra être payé.">
                        @csrf
                        <button type="submit" class="btn"
                                style="background:linear-gradient(135deg,#2563eb,#3b82f6);border:none;border-radius:10px;padding:.75rem;color:#fff;font-weight:600;">
                            <i class="fas fa-check me-1"></i> Valider le bulletin
                        </button>
                    </form>
                @endif

                {{-- Le règlement revient au Comptable (cahier des charges 5.2) --}}
                @if (! $paie->isPaye() && $paie->statut?->value !== 'annule' && auth()->user()->can('paie.payer'))
                    <form action="{{ route('rh.paies.payer', $paie) }}" method="POST" class="d-grid mb-2 form-confirm-delete"
                          data-confirm-title="Marquer comme payé ?"
                          data-confirm-text="Cette action enregistre le paiement effectif.">
                        @csrf
                        <button type="submit" class="btn"
                                style="background:linear-gradient(135deg,#1c7a4d,#2ecc71);border:none;border-radius:10px;padding:.75rem;color:#fff;font-weight:600;">
                            <i class="fas fa-money-bill-wave me-1"></i> Marquer comme payé
                        </button>
                    </form>
                @endif

                @if ($paie->isEnAttenteRetrait() && auth()->user()->can('paie.payer'))
                    <form action="{{ route('rh.paies.retrait', $paie) }}" method="POST" class="d-grid mb-2 form-confirm-delete"
                          data-confirm-title="Confirmer le retrait en espèces ?"
                          data-confirm-text="L'employé a bien récupéré sa paie à la billetterie.">
                        @csrf
                        <button type="submit" class="btn"
                                style="background:linear-gradient(135deg,#B8860B,#D4A94D);border:none;border-radius:10px;padding:.75rem;color:#fff;font-weight:600;">
                            <i class="fas fa-hand-holding-usd me-1"></i> Confirmer le retrait
                        </button>
                    </form>
                @endif

                @if (! $paie->statut?->isVerrouille() && auth()->user()->can('paie.gerer'))
                    <button type="button" class="btn w-100"
                            style="background:#fff;border:1px solid var(--school-red);color:var(--school-red);border-radius:10px;padding:.75rem;font-weight:600;"
                            data-bs-toggle="modal" data-bs-target="#annulerPaieShowModal">
                        <i class="fas fa-times me-1"></i> Annuler le bulletin
                    </button>
                @endif
            </div>
        @endcanany
    </div>
</div>

{{-- ============ MODALE ANNULATION ============ --}}
@can('paie.gerer')
    @if (! $paie->statut?->isVerrouille())
        <div class="modal fade" id="annulerPaieShowModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
                    <form action="{{ route('rh.paies.annuler', $paie) }}" method="POST">
                        @csrf
                        <div class="modal-header"
                             style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>Annuler le bulletin
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="mb-3">
                                Annuler le bulletin
                                <strong>{{ $paie->reference ?? ('#' . $paie->id) }}</strong> ?
                            </p>
                            <div>
                                <label class="form-label small fw-semibold">
                                    Motif <span class="text-danger">*</span>
                                </label>
                                <textarea name="motif" rows="3" class="form-control" required minlength="5"
                                          placeholder="Expliquez la raison de l'annulation..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn"
                                    style="background:var(--school-red-dark);color:#fff;border:none;border-radius:8px;font-weight:600;padding:.55rem 1.4rem;">
                                <i class="fas fa-times me-1"></i> Confirmer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endcan
@endsection