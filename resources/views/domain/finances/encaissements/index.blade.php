{{-- resources/views/admin/finances/encaissements/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Encaissements')
@section('page_icon', 'fa-cash-register')
@section('page_title', 'Encaissements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.paiements.index') }}">Finances</a></li>
    <li class="breadcrumb-item active">Encaissements</li>
@endsection

@section('css')
<style>
    .encaissements-page { --e-bg-soft: #faf6ee; --e-border: #ece4d6; }

    .caisse-banner {
        display: flex; align-items: center; gap: 12px; padding: .9rem 1.25rem;
        border-radius: 12px; margin-bottom: 1.25rem; font-weight: 600; font-size: .9rem;
    }
    .caisse-banner.ok { background: #e3f7ec; color: #1c7a4d; }
    .caisse-banner.ko { background: #fdf3e2; color: #b8720b; }
    .caisse-banner i { font-size: 1.2rem; }

    .zone-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; margin-bottom: 1.25rem; overflow: hidden; }
    .zone-header { background: var(--e-bg-soft); border-bottom: 1px solid var(--e-border); padding: .9rem 1.25rem; font-weight: 700; color: var(--school-red); font-size: .85rem; text-transform: uppercase; letter-spacing: .03em; display: flex; justify-content: space-between; align-items: center; }
    .zone-header i { margin-right: 8px; color: var(--school-gold-dark); }

    .table-encaissements thead th { font-size: .72rem; text-transform: uppercase; color: var(--school-muted); font-weight: 700; border-bottom: 2px solid var(--e-border); padding: .7rem .85rem; }
    .table-encaissements tbody td { padding: .7rem .85rem; border-bottom: 1px solid var(--e-border); font-size: .86rem; vertical-align: middle; }
    .table-encaissements tbody tr:last-child td { border-bottom: none; }

    .ref-paiement { font-family: monospace; font-size: .78rem; color: var(--school-muted); }
    .eleve-nom { font-weight: 700; font-size: .87rem; display: block; }

    .btn-encaisser {
        background: linear-gradient(135deg, #1c7a4d, #145c3a); color: #fff; border: none; border-radius: 8px;
        padding: .4rem 1rem; font-weight: 600; font-size: .82rem;
    }
    .btn-encaisser:hover { color: #fff; box-shadow: 0 4px 10px rgba(28,122,77,.3); }

    .btn-annuler-encaissement { background: #fff; border: 1px solid var(--e-border); color: var(--school-red); border-radius: 8px; padding: .35rem .85rem; font-size: .8rem; font-weight: 600; }
    .btn-annuler-encaissement:hover { background: #fdecee; border-color: var(--school-red); }

    .empty-state { padding: 2.5rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2rem; margin-bottom: .6rem; display: block; color: #d8c8ae; }

    .modal-encaissements { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-encaissements { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
    .btn-annuler { background: #fff; border: 1px solid var(--e-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: .5rem 1.3rem; }
</style>
@endsection

@section('contenu')
    <div class="encaissements-page">
        @if ($caisse)
            <div class="caisse-banner ok">
                <i class="fas fa-cash-register"></i>
                Caisse ouverte : <strong>{{ $caisse->libelle }}</strong>
            </div>
        @else
            <div class="caisse-banner ko">
                <i class="fas fa-triangle-exclamation"></i>
                Vous n'avez aucune caisse ouverte — l'ouverture de caisse n'est pas encore disponible dans l'application.
            </div>
        @endif

        {{-- File d'attente --}}
        <div class="zone-card">
            <div class="zone-header">
                <span><i class="fas fa-hourglass-half"></i>Paiements en attente d'encaissement</span>
                <span>{{ $enAttente->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-encaissements mb-0">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Élève / Payeur</th>
                            <th class="text-end">Montant</th>
                            <th>Comptable</th>
                            <th>Date de saisie</th>
                            <th class="text-center" style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enAttente as $detail)
                            <tr>
                                <td><span class="ref-paiement">{{ $detail->paiement?->reference }}</span></td>
                                <td>
                                    @if ($detail->paiement?->inscription?->eleve)
                                        <span class="eleve-nom">{{ $detail->paiement->inscription->eleve->nom }} {{ $detail->paiement->inscription->eleve->prenom }}</span>
                                    @endif
                                    <span class="text-muted small">{{ $detail->paiement?->payeur }}</span>
                                </td>
                                <td class="text-end"><strong>{{ number_format($detail->montant ?? 0, 0, ',', ' ') }} F</strong></td>
                                <td>{{ $detail->paiement?->utilisateur?->full_name ?? '—' }}</td>
                                <td>{{ $detail->date_paiement?->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    <form action="{{ route('finances.encaissements.encaisser', $detail) }}" method="POST"
                                          class="form-confirm-delete"
                                          data-confirm-title="Encaisser ce paiement ?"
                                          data-confirm-text="Confirme l'entrée effective de l'argent dans votre caisse.">
                                        @csrf
                                        <button type="submit" class="btn-encaisser"><i class="fas fa-check me-1"></i>Encaisser</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p class="mb-0">{{ $caisse ? 'Aucun paiement en attente.' : 'Ouvrez une caisse pour voir la file d\'attente.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Historique du jour --}}
        <div class="zone-card">
            <div class="zone-header">
                <span><i class="fas fa-history"></i>Mes encaissements du jour</span>
                <span>{{ $historiqueJour->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-encaissements mb-0">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Élève / Payeur</th>
                            <th class="text-end">Montant</th>
                            <th>Caisse</th>
                            <th>Date d'encaissement</th>
                            <th class="text-center" style="width:140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($historiqueJour as $detail)
                            <tr>
                                <td><span class="ref-paiement">{{ $detail->paiement?->reference }}</span></td>
                                <td>
                                    @if ($detail->paiement?->inscription?->eleve)
                                        <span class="eleve-nom">{{ $detail->paiement->inscription->eleve->nom }} {{ $detail->paiement->inscription->eleve->prenom }}</span>
                                    @endif
                                    <span class="text-muted small">{{ $detail->paiement?->payeur }}</span>
                                </td>
                                <td class="text-end"><strong>{{ number_format($detail->montant ?? 0, 0, ',', ' ') }} F</strong></td>
                                <td>{{ $detail->caisse?->libelle }}</td>
                                <td>{{ $detail->date_encaissement?->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn-annuler-encaissement btn-motif-annulation"
                                            data-url="{{ route('finances.encaissements.annuler', $detail) }}"
                                            data-bs-toggle="modal" data-bs-target="#motifAnnulationModal">
                                        <i class="fas fa-undo me-1"></i>Annuler
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-receipt"></i>
                                        <p class="mb-0">Aucun encaissement aujourd'hui.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modale motif d'annulation --}}
        <div class="modal fade" id="motifAnnulationModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content modal-encaissements">
                    <form id="motif-annulation-form" method="POST" action="">
                        @csrf
                        <div class="modal-header modal-header-encaissements">
                            <h5 class="modal-title"><i class="fas fa-undo me-2"></i>Annuler cet encaissement</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <label class="form-label">Motif <span class="text-danger">*</span></label>
                            <textarea name="motif" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-enregistrer">Confirmer l'annulation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.btn-motif-annulation', function () {
        $('#motif-annulation-form').attr('action', $(this).data('url'));
    });
});
</script>
@endpush