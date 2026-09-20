{{-- resources/views/admin/finances/paiements/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Paiements')
@section('page_icon', 'fa-cash-register')
@section('page_title', 'Paiements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances</li>
    <li class="breadcrumb-item active">Paiements</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.paiements.recherche') }}" class="btn btn-nouveau">
        <i class="fas fa-plus-circle me-1"></i> Nouveau paiement
    </a>
@endsection

@section('css')
<style>
    .paiements-page { --p-bg-soft: #faf6ee; --p-border: #ece4d6; }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--p-border) !important; }

    .filtre-label { font-size: .74rem; font-weight: 600; color: #5b6b7a; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 5px; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--p-border); padding: .5rem .8rem; font-size: .85rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #9aa7b2; font-size: .8rem; }
    .search-input { padding-left: 32px !important; }

    .btn-filtrer { background: var(--school-red); color: #fff; border: none; border-radius: 10px; padding: .5rem 1rem; font-weight: 600; font-size: .85rem; }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; }

    .compteur-badge {
        display: inline-block; background: var(--p-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .82rem; padding: .4rem .85rem; border-radius: 999px; border: 1px solid var(--p-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(200,30,58,.25); text-decoration: none; display: inline-flex; align-items: center;
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }

    .table-paiements thead tr { background: var(--p-bg-soft); }
    .table-paiements thead th {
        color: var(--school-red); font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--p-border); padding: .8rem .85rem; white-space: nowrap;
    }
    .table-paiements tbody td { padding: .7rem .85rem; border-bottom: 1px solid var(--p-border); font-size: .85rem; color: var(--school-ink); vertical-align: middle; }
    .table-paiements tbody tr:hover { background: #fdfaf5; }
    .table-paiements tbody tr:last-child td { border-bottom: none; }

    .ref-paiement { font-family: monospace; font-size: .78rem; color: var(--school-muted); }
    .eleve-nom { font-weight: 700; font-size: .87rem; display: block; }

    .badge-nature { display: inline-flex; align-items: center; font-size: .7rem; font-weight: 700; text-transform: uppercase; padding: .3em .7em; border-radius: 999px; background: var(--p-bg-soft); color: var(--school-gold-dark); }

    .badge-statut { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; padding: .35em .75em; border-radius: 999px; white-space: nowrap; }
    .badge-statut-attente { background: #fdf3e2; color: #b8720b; }
    .badge-statut-encaisse { background: #e3f7ec; color: #1c7a4d; }

    .empty-state { padding: 3rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }
    .empty-state a { color: var(--school-red); font-weight: 600; }

    /* .btn-action / .dropdown-menu-actions viennent de layouts/app.blade.php */
</style>
@endsection

@section('contenu')
    <div class="paiements-page">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="filtre-label">Statut</label>
                        <select name="statut" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach (\App\Domain\Finances\Types\StatutPaiement::cases() as $statut)
                                <option value="{{ $statut->value }}" {{ request('statut') == $statut->value ? 'selected' : '' }}>{{ $statut->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="filtre-label">Recherche</label>
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control filtre-input search-input"
                                   placeholder="Référence, payeur, libellé..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
                    </div>
                    <div class="col-6 col-md-1 text-end">
                        <span class="compteur-badge">{{ $details->total() }}</span>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-paiements align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Élève / Payeur</th>
                                <th>Nature</th>
                                <th>Libellé</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">Montant</th>
                                <th>Statut</th>
                                <th>Comptable</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($details as $detail)
                                @php $eleve = $detail->paiement?->inscription?->eleve; @endphp
                                <tr>
                                    <td><span class="ref-paiement">{{ $detail->paiement?->reference ?? '—' }}</span></td>
                                    <td>
                                        @if ($eleve)
                                            <span class="eleve-nom">{{ $eleve->nom }} {{ $eleve->prenom }}</span>
                                        @endif
                                        <span class="text-muted small">{{ $detail->paiement?->payeur }}</span>
                                    </td>
                                    <td><span class="badge-nature">{{ $detail->natureLabel() }}</span></td>
                                    <td>{{ $detail->libelle }}</td>
                                    <td class="text-center">{{ $detail->quantite ?? '—' }}</td>
                                    <td class="text-end"><strong>{{ number_format($detail->montant ?? 0, 0, ',', ' ') }} F</strong></td>
                                    <td>
                                        @if ($detail->statut_paiement?->value === 2)
                                            <span class="badge-statut badge-statut-encaisse"><i class="fas fa-check-circle"></i>Encaissé</span>
                                        @else
                                            <span class="badge-statut badge-statut-attente"><i class="fas fa-clock"></i>En attente</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->paiement?->utilisateur?->full_name ?? '—' }}</td>
                                    <td>{{ $detail->date_paiement?->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="fas fa-cash-register"></i>
                                            <p class="mb-0">Aucun paiement enregistré pour cette année.</p>
                                            <a href="{{ route('finances.paiements.recherche') }}">Enregistrer le premier paiement</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">
            {{ $details->appends(request()->query())->links() }}
        </div>
    </div>
@endsection