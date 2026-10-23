{{--
    Message affiché quand une liste est vide.

    colspan     dans un tableau : nombre de colonnes à couvrir (ajoute la ligne)
    icone       classes de l'icône (ex. « fas fa-inbox »)
    paragraphe  entoure le texte d'un paragraphe (défaut : oui)

    <x-vide colspan="5" icone="fas fa-user-friends">Aucun client.</x-vide>
--}}
@props(['colspan' => null, 'icone' => null, 'paragraphe' => true])
@if ($colspan)<tr><td colspan="{{ $colspan }}">@endif
<div {{ $attributes->class('empty-state') }}>@if ($icone)<i class="{{ $icone }}"></i>@endif @if ($paragraphe)<p>{{ $slot }}</p>@else{{ $slot }}@endif</div>
@if ($colspan)</td></tr>@endif
