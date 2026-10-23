{{--
    Carte principale d'un écran (liste, formulaire, tableau de bord).
    Les attributs passés (class, id, style...) s'ajoutent au bloc.

    <x-carte> ... </x-carte>
--}}
<div {{ $attributes->class('card main-card') }}>{{ $slot }}</div>
