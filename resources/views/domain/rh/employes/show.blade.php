@extends('admin.layouts.app')

@section('title', $employe->nom_prenom)
@section('page_icon', 'fa-user')
@section('page_title', 'Fiche employé')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item"><a href="{{ route('rh.employes.index') }}">Employés</a></li>
    <li class="breadcrumb-item active">{{ \Str::limit($employe->nom_prenom, 40) }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('rh.employes.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>

    <a href="{{ route('rh.paies.index', ['employe_id' => $employe->id]) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-money-check-alt me-1"></i> Voir ses bulletins
    </a>

    @can('employes.gerer')
        {{-- Redirige vers la liste avec l'employé pré-sélectionné pour édition.
             La modale existe là-bas : pas de duplication ici. --}}
        <a href="{{ route('rh.employes.index', ['edit' => $employe->id]) }}"
           class="btn btn-nouveau">
            <i class="fas fa-pen me-1"></i> Modifier
        </a>
    @endcan
@endsection

@section('css')
<style>
    .show-page { --s-bg-soft:#faf6ee; --s-border:#ece4d6; }

    .main-card {
        border:none; border-radius:16px;
        box-shadow: 0 10px 30px rgba(122,15,28,.06);
        background:#fff; overflow:hidden; margin-bottom:1rem;
    }
    .main-card .card-header { border-bottom:1px solid var(--s-border) !important; }
    .main-card .card-header h6 {
        font-size:.92rem; font-weight:700; color:var(--school-ink);
        display:flex; align-items:center; gap:8px;
    }
    .main-card .card-header i { color:var(--school-gold-dark); }

    /* Bandeau identité */
    .employe-banner {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        display:flex; align-items:center; gap:1.25rem;
        box-shadow: 0 8px 24px rgba(122,15,28,.18);
        margin-bottom:1.25rem;
    }
    .employe-avatar-lg {
        width:72px; height:72px; border-radius:50%;
        background:rgba(255,255,255,.18); border:2px solid rgba(255,255,255,.35);
        display:flex; align-items:center; justify-content:center;
        font-weight:800; font-size:1.5rem; letter-spacing:.5px;
        flex-shrink:0;
    }
    .employe-banner h2 { margin:0; font-size:1.3rem; font-weight:700; }
    .employe-banner .meta { font-size:.85rem; opacity:.9; margin-top:4px; }

    /* Lignes d'info */
    .info-row {
        display:flex; justify-content:space-between; padding:.55rem 0;
        border-bottom:1px solid #f1ece2;
    }
    .info-row:last-child { border-bottom:none; }
    .info-label {
        font-size:.75rem; color:var(--school-muted);
        text-transform:uppercase; letter-spacing:.5px; font-weight:600;
    }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }

    /* Badges */
    .badge-statut {
        display:inline-flex; align-items:center; gap:6px;
        padding:.35em .85em; border-radius:999px;
        font-size:.72rem; font-weight:600;
    }
    .badge-statut.actif   { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.inactif { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .badge-social {
        display:inline-flex; align-items:center; gap:6px;
        padding:.4em .9em; border-radius:999px;
        font-size:.75rem; font-weight:600;
        background:var(--s-bg-soft); color:var(--school-red); margin-right:6px;
    }

    /* Badge mode de paiement */
    .badge-mode {
        display:inline-flex; align-items:center; gap:6px;
        padding:.4em .9em; border-radius:999px;
        font-size:.75rem; font-weight:700;
    }
    .badge-mode.banque   { background:#e0ecfc; color:#2563eb; }
    .badge-mode.billette { background:#fdf4e0; color:var(--school-gold-dark); }

    /* Badge IRPP */
    .badge-irpp {
        display:inline-flex; align-items:center; gap:6px;
        padding:.4em .9em; border-radius:999px;
        font-size:.75rem; font-weight:600;
        background:#f3e5f5; color:#7b1fa2;
    }

    /* Bloc info banque */
    .bank-block {
        background:#f0f6ff; border-left:3px solid #2563eb;
        border-radius:6px; padding:.75rem 1rem; margin-top:.75rem;
        font-size:.85rem; color:#1e3a8a;
    }
    .bank-block .label { color:#5a7ac7; font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; font-weight:600; }
    .bank-block .value { font-weight:600; color:#1e3a8a; }
</style>
@endsection

@section('contenu')
@php
    // Pré-calculs pour éviter les @php inline dans le HTML
    $situationFamiliale = \App\Domain\Rh\Types\SituationFamiliale::tryFrom(
        (string) $employe->situation_familiale
    );
    $hasProtectionSociale = $employe->isAssureCnss()
        || $employe->isAssureAmu()
        || $employe->is_pension_validite;
@endphp

<div class="show-page">

    {{-- ============ BANDEAU ============ --}}
    <div class="employe-banner">
        <div class="employe-avatar-lg">
            {{ strtoupper(substr($employe->nom_prenom ?? '?', 0, 2)) }}
        </div>
        <div class="flex-grow-1">
            <h2>{{ $employe->nom_prenom }}</h2>
            <div class="meta">
                <i class="fas fa-briefcase me-1"></i>{{ $employe->profession?->libelle ?? 'Sans profession' }}
                @if ($employe->telephone)
                    &nbsp;·&nbsp;<i class="fas fa-phone me-1"></i>{{ $employe->telephone }}
                @endif
                @if ($employe->email)
                    &nbsp;·&nbsp;<i class="fas fa-envelope me-1"></i>{{ $employe->email }}
                @endif
            </div>
        </div>
        <div class="text-end">
            <span class="badge-statut {{ $employe->isActif() ? 'actif' : 'inactif' }}"
                  style="background:rgba(255,255,255,.2);color:#fff;">
                <span class="badge-dot"></span>{{ $employe->isActif() ? 'Actif' : 'Inactif' }}
            </span>
            <div style="font-size:1.1rem;font-weight:700;margin-top:.4rem;">
                {{ number_format((float) $employe->salaire, 0, ',', ' ') }} F
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- ============ COLONNE GAUCHE ============ --}}
        <div class="col-lg-6">

            {{-- Informations générales --}}
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4">
                    <h6><i class="fas fa-info-circle"></i> Informations générales</h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Nom &amp; prénom</span>
                        <span class="info-value">{{ $employe->nom_prenom }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Profession</span>
                        <span class="info-value">{{ $employe->profession?->libelle ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Salaire</span>
                        <span class="info-value">{{ number_format((float) $employe->salaire, 2, ',', ' ') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date d'embauche</span>
                        <span class="info-value">
                            {{ optional($employe->date_embauche)->format('d/m/Y') ?? '—' }}
                            @if ($employe->date_embauche)
                                <span class="text-xs text-muted">({{ $employe->anciennete }} ans)</span>
                            @endif
                        </span>
                    </div>
                    @if ($employe->date_fin_contrat)
                        <div class="info-row">
                            <span class="info-label">Fin de contrat</span>
                            <span class="info-value">{{ $employe->date_fin_contrat->format('d/m/Y') }}</span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">N° CNSS</span>
                        <span class="info-value" style="font-family:monospace;">{{ $employe->numero_cnss ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date de naissance</span>
                        <span class="info-value">
                            {{ optional($employe->date_naissance)->format('d/m/Y') ?? '—' }}
                            @if ($employe->date_naissance)
                                <span class="text-xs text-muted">({{ $employe->age }} ans)</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lieu de naissance</span>
                        <span class="info-value">{{ $employe->lieu_naissance ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Situation familiale</span>
                        <span class="info-value">{{ $situationFamiliale?->label() ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nombre d'enfants</span>
                        <span class="info-value">{{ $employe->nbre_enfant ?? 0 }}</span>
                    </div>
                </div>
            </div>

            {{-- Protections sociales --}}
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4">
                    <h6><i class="fas fa-shield-alt"></i> Protections sociales</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        @if ($employe->isAssureCnss())
                            <span class="badge-social"><i class="fas fa-check-circle"></i> CNSS</span>
                        @endif
                        @if ($employe->isAssureAmu())
                            <span class="badge-social"><i class="fas fa-check-circle"></i> AMU</span>
                        @endif
                        @if ($employe->is_pension_validite)
                            <span class="badge-social"><i class="fas fa-check-circle"></i> Pension validité</span>
                        @endif
                        @unless ($hasProtectionSociale)
                            <span class="text-muted">Aucune protection sociale enregistrée.</span>
                        @endunless
                    </div>

                    @if ($employe->taux_pension_validite)
                        <div class="info-row">
                            <span class="info-label">Taux pension</span>
                            <span class="info-value">{{ $employe->taux_pension_validite }} %</span>
                        </div>
                    @endif
                    @if ($employe->nature_pension_validite)
                        <div class="info-row">
                            <span class="info-label">Nature pension</span>
                            <span class="info-value">
                                {{ number_format((float) $employe->nature_pension_validite, 2, ',', ' ') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ============ COLONNE DROITE ============ --}}
        <div class="col-lg-6">

            {{-- Contact & adresse --}}
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4">
                    <h6><i class="fas fa-address-book"></i> Contact &amp; adresse</h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Téléphone</span>
                        <span class="info-value">{{ $employe->telephone ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $employe->email ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Quartier</span>
                        <span class="info-value">{{ $employe->quartier ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ville</span>
                        <span class="info-value">{{ $employe->ville ?? '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Mode de paiement & fiscalité --}}
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4">
                    <h6><i class="fas fa-wallet"></i> Mode de paiement &amp; fiscalité</h6>
                </div>
                <div class="card-body p-4">

                    {{-- Mode de paiement --}}
                    <div class="info-row">
                        <span class="info-label">Mode de paiement</span>
                        <span class="info-value">
                            @if ($employe->isPayeParBanque())
                                <span class="badge-mode banque">
                                    <i class="fas fa-university"></i> Banque
                                </span>
                            @else
                                <span class="badge-mode billette">
                                    <i class="fas fa-money-bill-wave"></i> Billetterie
                                </span>
                            @endif
                        </span>
                    </div>

                    {{-- Coordonnées bancaires si mode banque --}}
                    @if ($employe->isPayeParBanque() && ($employe->banque || $employe->iban || $employe->titulaire_compte))
                        <div class="bank-block">
                            <div class="row g-2">
                                @if ($employe->banque)
                                    <div class="col-md-6">
                                        <div class="label">Banque</div>
                                        <div class="value">{{ $employe->banque }}</div>
                                    </div>
                                @endif
                                @if ($employe->iban)
                                    <div class="col-md-6">
                                        <div class="label">IBAN / RIB</div>
                                        <div class="value" style="font-family:monospace;">{{ $employe->iban }}</div>
                                    </div>
                                @endif
                                @if ($employe->titulaire_compte)
                                    <div class="col-12">
                                        <div class="label">Titulaire du compte</div>
                                        <div class="value">{{ $employe->titulaire_compte }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Mode IRPP --}}
                    <div class="info-row" style="margin-top:.75rem;">
                        <span class="info-label">Mode IRPP</span>
                        <span class="info-value">
                            @if (($employe->irpp_mode ?? 'auto') === 'auto')
                                <span class="badge-irpp">
                                    <i class="fas fa-calculator"></i> Automatique (barème)
                                </span>
                            @else
                                <span class="badge-irpp">
                                    <i class="fas fa-hand-paper"></i> Manuel (montant fixe)
                                </span>
                            @endif
                        </span>
                    </div>

                    @if (($employe->irpp_mode ?? 'auto') === 'manuel')
                        <div class="alert alert-light border-0 small mt-3 mb-0"
                             style="background:#faf6ee;color:var(--school-muted);">
                            <i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i>
                            Le montant d'IRPP est saisi manuellement dans le module
                            <a href="{{ route('rh.remunerations.index', ['employe_id' => $employe->id]) }}"
                               style="color:var(--school-red);text-decoration:underline;">
                                Rémunérations
                            </a>.
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection