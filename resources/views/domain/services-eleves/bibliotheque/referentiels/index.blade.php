@extends('admin.layouts.app')

@section('title', 'Référentiels bibliothèque')
@section('page_icon', 'fa-cog')
@section('page_title', 'Référentiels de la bibliothèque')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.bibliotheque.livres.index') }}">Bibliothèque</a></li>
    <li class="breadcrumb-item active">Référentiels</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.bibliotheque.livres.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-book me-1"></i> Catalogue
    </x-bouton>
@endsection

@section('css')
<style>
    .ref-page { --r-bg-soft:var(--eim-cream); --r-border:var(--eim-border); }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .nav-tabs-custom { border-bottom:1px solid var(--r-border); padding:0 1.25rem; }
    .nav-tabs-custom .nav-link { border:none; color:var(--school-muted); font-weight:600; font-size:.88rem; padding:.85rem 1rem; border-bottom:2px solid transparent; }
    .nav-tabs-custom .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }

    .ref-item {
        display:flex; align-items:center; justify-content:space-between;
        padding:.75rem 1rem; border-bottom:1px solid var(--r-border);
        transition:background .15s;
    }
    .ref-item:hover { background:var(--eim-hover); }
    .ref-item:last-child { border-bottom:none; }
    .ref-item-label { font-weight:600; color:var(--school-ink); }
    .ref-item-count { font-size:.75rem; color:var(--school-muted); }

    .ref-actions { display:flex; gap:.5rem; }
    .btn-ref-action {
        background:transparent; border:1px solid var(--r-border); color:var(--school-muted);
        border-radius:6px; padding:.25rem .6rem; font-size:.75rem; cursor:pointer;
        transition:all .15s;
    }
    .btn-ref-action:hover { background:var(--school-red); color:#fff; border-color:var(--school-red); }

    .ref-add-form {
        display:flex; gap:.5rem; padding:1rem;
        background:var(--r-bg-soft); border-bottom:1px solid var(--r-border);
    }
    .ref-add-form input {
        flex:1; border-radius:8px; border:1px solid var(--r-border);
        padding:.55rem .75rem; font-size:.88rem;
    }
    .ref-add-form button {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; border:none; border-radius:8px; font-weight:600;
        padding:.55rem 1.2rem; font-size:.88rem;
    }
</style>
@endsection

@section('contenu')
<div class="ref-page">
    <x-carte>
        <div class="card-header bg-white p-0 border-bottom-0">
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-auteurs" type="button">
                        <i class="fas fa-user-edit me-1"></i> Auteurs ({{ $auteurs->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-maisons" type="button">
                        <i class="fas fa-building me-1"></i> Maisons d'édition ({{ $maisons->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button">
                        <i class="fas fa-tags me-1"></i> Catégories ({{ $categories->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-annees" type="button">
                        <i class="fas fa-calendar-alt me-1"></i> Années d'édition ({{ $annees->count() }})
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content">

                {{-- Auteurs --}}
                <div class="tab-pane fade show active" id="tab-auteurs">
                    @include('domain.services-eleves.bibliotheque.referentiels._partials.list', [
                        'type' => 'auteur', 'items' => $auteurs, 'field' => 'nom_prenom', 'label' => 'Auteur'
                    ])
                </div>

                {{-- Maisons --}}
                <div class="tab-pane fade" id="tab-maisons">
                    @include('domain.services-eleves.bibliotheque.referentiels._partials.list', [
                        'type' => 'maison', 'items' => $maisons, 'field' => 'libelle', 'label' => 'Maison d\'édition'
                    ])
                </div>

                {{-- Catégories --}}
                <div class="tab-pane fade" id="tab-categories">
                    @include('domain.services-eleves.bibliotheque.referentiels._partials.list', [
                        'type' => 'categorie', 'items' => $categories, 'field' => 'libelle', 'label' => 'Catégorie'
                    ])
                </div>

                {{-- Années --}}
                <div class="tab-pane fade" id="tab-annees">
                    @include('domain.services-eleves.bibliotheque.referentiels._partials.list', [
                        'type' => 'annee', 'items' => $annees, 'field' => 'libelle', 'label' => 'Année d\'édition'
                    ])
                </div>
            </div>
        </div>
    </x-carte>
</div>
@endsection