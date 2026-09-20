{{-- resources/views/admin/finances/paiements/recherche.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Paiements')
@section('page_icon', 'fa-cash-register')
@section('page_title', 'Paiements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.paiements.index') }}">Finances</a></li>
    <li class="breadcrumb-item active">Nouveau paiement</li>
@endsection

@section('css')
<style>
    .paiements-page { --p-bg-soft: #faf6ee; --p-border: #ece4d6; }
    .search-card { max-width: 640px; margin: 2rem auto; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.08); padding: 2rem; }
    .search-card h4 { font-family: var(--school-ff-display); color: var(--school-red-dark); text-align: center; margin-bottom: 1.5rem; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--p-border); padding: .6rem 1rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-filtrer { background: var(--school-red); color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600; }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; }
    .resultat-item {
        display: flex; align-items: center; justify-content: space-between; padding: .8rem 1rem;
        border: 1px solid var(--p-border); border-radius: 10px; margin-top: .6rem; text-decoration: none; color: var(--school-ink);
    }
    .resultat-item:hover { background: var(--p-bg-soft); border-color: var(--school-red); color: var(--school-ink); }
    .resultat-item .nom { font-weight: 700; }
    .resultat-item .matricule { font-family: monospace; font-size: .8rem; color: var(--school-muted); }
</style>
@endsection

@section('contenu')
    <div class="paiements-page">
        <div class="search-card">
            <h4><i class="fas fa-search me-2"></i>Rechercher un élève</h4>
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control filtre-input" placeholder="Nom, prénom ou matricule..." value="{{ $search }}" autofocus>
                <button type="submit" class="btn btn-filtrer"><i class="fas fa-search"></i></button>
            </form>

            @if ($search)
                @forelse ($resultats as $eleve)
                    <a href="{{ route('finances.paiements.show', $eleve) }}" class="resultat-item">
                        <div>
                            <span class="nom">{{ $eleve->nom }} {{ $eleve->prenom }}</span>
                            <span class="matricule d-block">{{ $eleve->matricule ?? '—' }}</span>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                @empty
                    <p class="text-center text-muted mt-3 mb-0">Aucun élève trouvé.</p>
                @endforelse
            @endif
        </div>
    </div>
@endsection