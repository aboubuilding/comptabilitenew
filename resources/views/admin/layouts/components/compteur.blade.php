{{--
    Pastille « N résultat(s) » d'une barre de filtres.

    <x-compteur>{{ $clients->total() }} client(s)</x-compteur>
--}}
<span {{ $attributes->class('compteur-badge') }}>{{ $slot }}</span>
