{{--
    Entrée « Désactiver / Supprimer » d'un menu d'actions : formulaire DELETE
    avec demande de confirmation (gestionnaire .form-confirm-delete du gabarit).

    action  adresse de suppression
    icone   classes de l'icône (défaut : corbeille)
    Les autres attributs (data-confirm-title, data-confirm-text...) vont sur le formulaire.

    <x-actions.supprimer :action="route('logistique.clients.destroy', $c)">Désactiver</x-actions.supprimer>
--}}
@props(['action', 'icone' => 'fas fa-trash'])
<li>
    <form action="{{ $action }}" method="POST" {{ $attributes->class('form-confirm-delete') }}>
        @csrf @method('DELETE')
        <button type="submit" class="dropdown-item text-danger"><i class="{{ $icone }}"></i> {{ $slot->isEmpty() ? 'Désactiver' : $slot }}</button>
    </form>
</li>
