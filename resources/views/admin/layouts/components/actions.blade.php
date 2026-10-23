{{--
    Menu « ... » des actions d'une ligne de tableau. Le contenu est une suite
    de <li> (liens, boutons, <x-actions.supprimer>). Sans action autorisée
    pour le profil, le bouton est masqué (règle CSS du gabarit).

    <x-actions>
        <li><a class="dropdown-item" href="..."><i class="fas fa-eye"></i> Voir</a></li>
        <x-actions.supprimer :action="route('...destroy', $ligne)" />
    </x-actions>
--}}
<div {{ $attributes->class('dropdown') }}>
    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">{{ $slot }}</ul>
</div>
