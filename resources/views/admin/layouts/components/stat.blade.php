{{--
    Chiffre clé d'un écran : icône, valeur (contenu), libellé.

    icone    classes de l'icône (ex. « fas fa-coins »)
    couleur  gold, green, blue ou red
    libelle  texte sous la valeur (attribut, ou <x-slot:libelle> s'il contient du HTML)
    <x-slot:complement>  contenu ajouté sous le libellé (sous-total, précision)
    valeur-id, valeur-class  id et classes de la valeur (compteur mis à jour en JavaScript, couleur)

    <x-stat icone="fas fa-coins" couleur="green" libelle="Encaissé ce mois">{{ $total }} F</x-stat>
--}}
@props(['icone' => null, 'couleur' => null, 'libelle' => null, 'valeurId' => null, 'valeurClass' => null])
<div {{ $attributes->class('stat-card') }}>
    <div @class(['stat-icon', $couleur])>@if ($icone)<i class="{{ $icone }}"></i>@endif</div>
    <div class="stat-info">
        <div @class(['stat-value', $valeurClass]) @if ($valeurId) id="{{ $valeurId }}" @endif>{{ $slot }}</div>
        <div class="stat-label">{{ $libelle }}</div>
        {{ $complement ?? '' }}
    </div>
</div>
