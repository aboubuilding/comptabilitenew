{{--
    Champ de recherche d'une barre de filtres. Les attributs (name, value,
    placeholder...) vont sur le champ.

    <x-recherche name="search" placeholder="Nom, téléphone..." value="{{ $filters['search'] ?? '' }}" />
--}}
<div class="search-wrapper">
    <i class="fas fa-search search-icon"></i>
    <input {{ $attributes->class('form-control filtre-input search-input')->merge(['type' => 'text']) }}>
</div>
