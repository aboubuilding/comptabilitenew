{{--
    Tableau de liste, défilable sur petit écran. Le contenu est celui d'un
    <table> (thead, tbody, tfoot) ; les attributs vont sur la balise <table>.

    <x-tableau class="table-ventes align-middle mb-0" id="table-clients"> ... </x-tableau>
--}}
<div class="table-responsive"><table {{ $attributes->class('table') }}>{{ $slot }}</table></div>
