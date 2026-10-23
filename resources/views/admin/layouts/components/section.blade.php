{{--
    Bloc d'un formulaire, avec un titre facultatif.

    titre  titre du bloc
    icone  classes de l'icône du titre

    <x-section titre="Coordonnées" icone="fas fa-address-book"> ... </x-section>
--}}
@props(['titre' => null, 'icone' => null])
<div {{ $attributes->class('form-section') }}>
    @if ($titre)<div class="form-section-title">@if ($icone)<i class="{{ $icone }}"></i>@endif {{ $titre }}</div>@endif
    {{ $slot }}
</div>
