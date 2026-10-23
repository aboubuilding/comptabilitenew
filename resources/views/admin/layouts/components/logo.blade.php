{{--
    Logo « EIM Compta » (fichiers dans public/images/logo).

    variant   sceau (rond, par défaut), lockup (sceau + nom) ou icon (carré, favicon)
    size      hauteur en pixels (largeur proportionnelle)
    decoratif logo à côté d'un texte qui le nomme déjà : alt vide, ignoré des lecteurs d'écran

    <x-logo variant="lockup" :size="56" />
    Si le fichier manque, on retombe sur le logo historique de l'école.
--}}
@props(['variant' => 'sceau', 'size' => null, 'decoratif' => false])
@php
    $fichier = 'images/logo/eim-compta-' . (in_array($variant, ['sceau', 'lockup', 'icon'], true) ? $variant : 'sceau') . '.svg';
    $src = is_file(public_path($fichier)) ? asset($fichier) : asset('images/logo-mariam.png');
@endphp
<img src="{{ $src }}"
     alt="{{ $decoratif ? '' : 'EIM Compta – École Internationale Mariam' }}"
     @if ($decoratif) aria-hidden="true" @endif
     @if ($size) height="{{ $size }}" @endif
     {{ $attributes->class(['eim-logo', 'eim-logo-' . $variant]) }}>
