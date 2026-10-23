{{--
    Bouton de l'application. Avec un attribut href, c'est un lien.

    variante  nouveau (action principale de l'écran), enregistrer, annuler,
              filtrer ou contour (action secondaire : exporter, imprimer...)
    Les autres attributs (type, id, data-bs-*...) vont sur la balise.

    <x-bouton variante="nouveau" data-bs-toggle="modal" data-bs-target="#clientModal">Nouveau client</x-bouton>
    <x-bouton variante="contour" href="{{ route('...export') }}">Exporter</x-bouton>
--}}
@props(['variante' => 'enregistrer'])
@php $classes = 'btn btn-' . ($variante === 'contour' ? 'outline-scolarite' : $variante); @endphp
@if ($attributes->has('href'))<a {{ $attributes->class($classes) }}>{{ $slot }}</a>@else<button {{ $attributes->class($classes) }}>{{ $slot }}</button>@endif
