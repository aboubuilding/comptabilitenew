{{--
    Pagination d'une liste (gabarit commun vendor/pagination/custom).

    liste  le résultat paginé, avec ses éventuels ->appends(...)

    <x-pagination :liste="$clients->appends(request()->query())" />
--}}
@props(['liste'])
{{ $liste->links('vendor.pagination.custom') }}
