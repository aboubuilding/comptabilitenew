{{--
    Libellé d'un champ de formulaire.

    requis  ajoute l'astérisque rouge des champs obligatoires

    <x-libelle requis>Nom / Raison sociale</x-libelle>
--}}
@props(['requis' => false])
<label {{ $attributes->class('form-label') }}>{{ $slot }}@if ($requis) <span class="text-danger">*</span>@endif</label>
