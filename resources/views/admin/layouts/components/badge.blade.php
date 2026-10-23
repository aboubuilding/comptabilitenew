{{--
    Pastille de statut. La couleur vient de la classe passée (attente,
    encaisse, annule, bg-success...).

    point  affiche le point coloré devant le libellé

    <x-badge class="attente" point>En attente</x-badge>
--}}
@props(['point' => false])
<span {{ $attributes->class('badge-statut') }}>@if ($point)<span class="badge-dot"></span>@endif{{ $slot }}</span>
