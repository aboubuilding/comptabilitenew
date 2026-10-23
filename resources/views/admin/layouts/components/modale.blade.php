{{--
    Fenêtre modale : en-tête (titre + croix), corps (contenu), pied (boutons).

    Emplacements
      titre       titre de la fenêtre ; ses attributs (id...) vont sur le <h5>
      pied        boutons du bas ; ses attributs (class...) vont sur le pied
      formulaire  si présent, la fenêtre est un formulaire : ses attributs
                  (id, action, method...) vont sur le <form>, son contenu
                  (@@csrf, champs cachés) est placé en tête du formulaire

    Paramètres
      dialogue       classes de taille/position (défaut : modal-dialog-centered)
      contenu-class, contenu-style, contenu-id   bloc .modal-content
      entete-class,  entete-style                en-tête
      corps-class,   corps-style,  corps-id      corps
      croix-blanche  croix de fermeture claire (en-tête coloré)
      form-contenu   le <form> est lui-même le bloc .modal-content
    Les autres attributs (id, data-bs-backdrop...) vont sur la fenêtre.

    <x-modale id="clientModal" data-bs-backdrop="static" croix-blanche>
        <x-slot:formulaire id="client-form">@@csrf</x-slot:formulaire>
        <x-slot:titre id="client-title"><i class="fas fa-user-plus me-2"></i>Nouveau client</x-slot:titre>
        ... champs ...
        <x-slot:pied>
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">Enregistrer</x-bouton>
        </x-slot:pied>
    </x-modale>
--}}
@props([
    'dialogue' => 'modal-dialog-centered',
    'contenuClass' => null, 'contenuStyle' => null, 'contenuId' => null,
    'enteteClass' => null, 'enteteStyle' => null,
    'corpsClass' => null, 'corpsStyle' => null, 'corpsId' => null,
    'croixBlanche' => false, 'formContenu' => false,
])
@php
    $formulaire ??= null;
    $contenu = (new \Illuminate\View\ComponentAttributeBag(array_filter(['style' => $contenuStyle, 'id' => $contenuId])))
        ->class(['modal-content', $contenuClass]);
@endphp
<div {{ $attributes->class('modal fade')->merge(['tabindex' => '-1']) }}>
    <div @class(['modal-dialog', $dialogue])>
        @if ($formulaire && $formContenu)
            <form {{ $formulaire->attributes->merge($contenu->getAttributes()) }}>
        @else
            <div {{ $contenu }}>
            @if ($formulaire)<form {{ $formulaire->attributes }}>@endif
        @endif
        {{ $formulaire }}
        @isset($titre)
            <div @class(['modal-header', $enteteClass]) @if ($enteteStyle) style="{{ $enteteStyle }}" @endif>
                <h5 {{ $titre->attributes->class('modal-title') }}>{{ $titre }}</h5>
                <button type="button" @class(['btn-close', 'btn-close-white' => $croixBlanche]) data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
        @endisset
        @if ($slot->isNotEmpty() || isset($titre) || isset($pied))
            <div @class(['modal-body', $corpsClass]) @if ($corpsStyle) style="{{ $corpsStyle }}" @endif @if ($corpsId) id="{{ $corpsId }}" @endif>{{ $slot }}</div>
        @endif
        @isset($pied)
            <div {{ $pied->attributes->class('modal-footer') }}>{{ $pied }}</div>
        @endisset
        @if ($formulaire && $formContenu)
            </form>
        @else
            @if ($formulaire)</form>@endif
            </div>
        @endif
    </div>
</div>
