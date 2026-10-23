@extends('admin.layouts.app')

@section('title', $livre->titre)
@section('page_icon', 'fa-book')
@section('page_title', 'Fiche du livre')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.bibliotheque.livres.index') }}">Bibliothèque</a></li>
    <li class="breadcrumb-item active">{{ \Str::limit($livre->titre, 40) }}</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.bibliotheque.livres.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </x-bouton>
    @can('bibliotheque.preter')
        @if ($livre->nombre_disponibles > 0)
            <x-bouton variante="nouveau" type="button" data-bs-toggle="modal" data-bs-target="#pretShowModal">
                <i class="fas fa-hand-holding me-1"></i> Prêter
            </x-bouton>
        @endif
    @endcan
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--eim-border) !important; }

    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }
    .info-value.grand { font-size:1.15rem; color:var(--school-red-dark); }

    .dispo-card {
        background:linear-gradient(135deg,var(--eim-success),#2ecc71);
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        box-shadow:0 8px 24px rgba(28,122,77,.18);
        margin-bottom:1.25rem;
    }
    .dispo-card.indispo { background:linear-gradient(135deg,var(--school-red-deep),var(--school-red)); }
    .dispo-card .label { font-size:.8rem; opacity:.85; text-transform:uppercase; letter-spacing:.5px; }
    .dispo-card .value { font-size:2rem; font-weight:700; margin-top:4px; }

    .table-historique thead tr { background:var(--eim-cream); }
    .table-historique thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid var(--eim-border); }
    .table-historique tbody td { padding:.7rem .9rem; border-bottom:1px solid var(--eim-border); font-size:.85rem; vertical-align:middle; }
    .table-historique tbody tr:hover { background:var(--eim-hover); }

</style>
@endsection

@section('contenu')
<div class="row g-4">

    {{-- Colonne gauche --}}
    <div class="col-lg-4">
        {{-- Carte disponibilité --}}
        <div class="dispo-card {{ $livre->nombre_disponibles === 0 ? 'indispo' : '' }}">
            <div class="label">Exemplaires disponibles</div>
            <div class="value">{{ $livre->nombre_disponibles }} / {{ $livre->nombre_exemplaires }}</div>
            @if ($livre->nombre_disponibles === 0)
                <div class="mt-2"><i class="fas fa-times-circle me-1"></i> Aucun exemplaire disponible</div>
            @endif
        </div>

        {{-- Infos --}}
        <x-carte>
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i> Informations</h6>
            </div>
            <div class="card-body p-4">
                <div class="info-row">
                    <span class="info-label">Titre</span>
                    <span class="info-value">{{ $livre->titre }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Auteur</span>
                    <span class="info-value">{{ $livre->auteur?->nom_prenom ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Catégorie</span>
                    <span class="info-value">{{ $livre->categorie?->libelle ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Éditeur</span>
                    <span class="info-value">{{ $livre->maisonEdition?->libelle ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Année édition</span>
                    <span class="info-value">{{ $livre->anneeEdition?->libelle ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">N° interne</span>
                    <span class="info-value" style="font-family:monospace;">{{ $livre->numero ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ISBN</span>
                    <span class="info-value" style="font-family:monospace;">{{ $livre->numero_isbn ?? '—' }}</span>
                </div>
                @if ($livre->description)
                    <div class="mt-3 p-2 rounded" style="background:var(--eim-cream);font-size:.85rem;color:var(--eim-ink-soft);">
                        <strong>Description :</strong> {{ $livre->description }}
                    </div>
                @endif
            </div>
        </x-carte>
    </div>

    {{-- Colonne droite --}}
    <div class="col-lg-8">
        <x-carte>
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-history me-1" style="color:var(--school-gold-dark);"></i> Historique des prêts</h6>
                <span class="text-xs text-muted">{{ $livre->prets->count() }} prêt(s)</span>
            </div>
            <div class="card-body p-0">
                @if ($livre->prets->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-2x mb-2 d-block" style="color:#d8c8ae;"></i>
                        Aucun prêt enregistré pour ce livre.
                    </div>
                @else
                    <x-tableau class="table-historique mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Élève</th>
                                    <th>Prêt</th>
                                    <th>Retour prévu</th>
                                    <th>Retour réel</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($livre->prets as $p)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">
                                            {{ trim(($p->inscription?->eleve?->nom ?? '') . ' ' . ($p->inscription?->eleve?->prenom ?? '')) }}
                                        </div>
                                        <div class="text-xs text-muted">{{ $p->inscription?->eleve?->matricule }}</div>
                                    </td>
                                    <td class="text-sm">{{ optional($p->date_pret)->format('d/m/Y') }}</td>
                                    <td class="text-sm">{{ optional($p->date_retour_pret)->format('d/m/Y') }}</td>
                                    <td class="text-sm">{{ optional($p->date_retour_reel)->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        <x-badge class="{{ $p->statut_pret?->css() }}" point>{{ $p->statut_pret?->label() }}
                                        </x-badge>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </x-tableau>
                @endif
            </div>
        </x-carte>
    </div>
</div>
@endsection