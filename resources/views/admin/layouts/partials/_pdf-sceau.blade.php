{{--
    Sceau « EIM Compta » pour les en-têtes PDF (DomPDF) : image PNG embarquée
    en base64 (DomPDF n'affiche pas le SVG du sceau). Repli sur le logo
    historique de l'école si le fichier manque.

    @include('admin.layouts.partials._pdf-sceau', ['taille' => '17mm'])
--}}
@php
    $cheminSceau = collect([public_path('images/logo/eim-compta-sceau.png'), public_path('images/logo-mariam.png')])->first(fn ($c) => is_file($c));
    $tailleSceau = $taille ?? '17mm';
@endphp
@if ($cheminSceau)
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($cheminSceau)) }}" alt="EIM Compta – École Internationale Mariam" style="width: {{ $tailleSceau }}; height: {{ $tailleSceau }};">
@endif
