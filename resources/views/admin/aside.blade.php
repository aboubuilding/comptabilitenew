<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            <li><a class=" " href="{{ url('/') }}" aria-expanded="false">
                    <i class="material-symbols-outlined">home</i>
                    <span class="nav-text">Tableau de bord </span>
                </a>




            </li>


            @php
                $user_value = session()->get('LoginUser');
                $compte_id = $user_value['compte_id'];

                $user = App\Models\User::rechercheUserById($compte_id);

                $role = $user->role;

            @endphp



            <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                    <i class="material-symbols-outlined">school</i>
                    <span class="nav-text">Inscriptions </span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="{{ url('/inscriptions/qrcode') }}">Qr Code </a></li>
                    <li><a href="{{ url('/inscriptions/get-location') }}">Géolocalisation </a></li>
                    <li><a href="{{ url('/inscriptions/locations') }}">Localisations </a></li>
                    @if ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN)
                        <li><a href="{{ url('/inscriptions/cycles') }}">Cycles </a></li>
                        <li><a href="{{ url('/inscriptions/niveaux') }}">Niveaux </a></li>
                        <li><a href="{{ url('/inscriptions/classes') }}">Classes </a></li>
                        <li><a href="{{ url('/inscriptions/classes') }}">Eleves </a></li>
                        <li><a href="{{ url('/chiffres/classes') }}">Modifier une inscription </a></li>
                    @endif
                </ul>

            </li>


            <li>
                <a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                    <i class="material-icons"> app_registration </i>
                    <span class="nav-text">Paiements </span>
                </a>
                <ul aria-expanded="false">



                    <li><a href="{{ url('/paiements/add') }}">Ajouter </a></li>
                    <li><a href="{{ url('/paiements/index') }}">Tous les paiements </a></li>

                    <li><a href="{{ url('/paiements/details') }}">Tous les details </a></li>
                    <li><a href="{{ url('/paiements/nonencaisse') }}">Non encaisses </a></li>
                    <li><a href="{{ url('/banques/index') }}">Banques </a></li>
                    <li><a href="{{ url('/cheques/index') }}">Cheques </a></li>
                    <li><a href="{{ url('/articles/index') }}">Produits en vente </a></li>
                    <li><a href="{{ url('/activites/index') }}">Activites </a></li>
                    <li><a href="{{ url('/souscriptions/index') }}">Souscriptions </a></li>




                </ul>

            </li>


            @if ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN)
                <li>
                    <a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-icons"> app_registration </i>
                        <span class="nav-text">Recouvrements </span>
                    </a>
                    <ul aria-expanded="false">

                        <li><a href="{{ url('/recouvrements/scolarite') }}">Scolarités </a></li>
                        <li><a href="{{ url('/recouvrements/cantine') }}">Cantines </a></li>
                        <li><a href="{{ url('/recouvrements/bus') }}">Bus </a></li>

                        <li><a href="{{ url('/recouvrements/examen') }}">Frais d 'examens ' </a></li>




                    </ul>

                </li>
            @endif


            <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                    <i class="material-icons"> table_chart </i>
                    <span class="nav-text">Caisses </span>
                </a>
                <ul aria-expanded="false">


                    <li><a href="{{ url('/caisses/index') }}">Toutes les caisses </a></li>
                    <li><a href="{{ url('/encaissements/index') }}">Tous les encaissements </a></li>
                    <li><a href="{{ url('/decaissements/index') }}">Tous les decaissements </a></li>
                    <li><a href="{{ url('/mouvements/index') }}">Tous les mouvements </a></li>

                    @if ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN)
                        <li><a href="{{ url('/caissiers/index') }}">Tous les caissiers </a></li>
                    @endif



                </ul>

            </li>


            <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                    <i class="material-icons">folder</i>
                    <span class="nav-text">Dépenses </span>
                </a>
                <ul aria-expanded="false">

                    <li><a href="{{ url('/depenses/index') }}">Toutes les depenses </a></li>

                    @if ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN)
                        <li><a href="{{ url('/depenses/valides') }}">Valider les depenses </a></li>
                    @endif

                </ul>

            </li>


            @if ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN)
                <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-symbols-outlined">person</i>
                        <span class="nav-text">Parc </span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ url('/cars/tableau') }}">Tableau de bord </a></li>
                        <li><a href="{{ url('/zones/index') }}">Zones </a></li>
                        <li><a href="{{ url('/cars/index') }}">Souscriptions </a></li>
                        <li><a href="{{ url('/voitures/index') }}">Voitures </a></li>
                        <li><a href="{{ url('/chauffeurs/index') }}">Chauffeurs </a></li>
                        <li><a href="{{ url('/lignes/index') }}">Lignes de bus </a></li>
                        <li><a href="{{ url('/paiements/parc') }}">Tous les paiements </a></li>
                        <li><a href="{{ url('/depenses/parc') }}">Toutes les depenses </a></li>



                    </ul>

                </li>


                <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-symbols-outlined">person</i>
                        <span class="nav-text">Cantines </span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ url('/cantines/tableau') }}">Tableau de bord </a></li>
                        <li><a href="{{ url('/paiements/cantine') }}">Paiements </a></li>

                        <li><a href="{{ url('/achats/index') }}">Achats </a></li>

                        <li><a href="{{ url('/stocks/index') }}">Stocks </a></li>

                        <li><a href="{{ url('/magasins/index') }}">Magasins </a></li>
                        <li><a href="{{ url('/produits/index') }}">Produits </a></li>
                        <li><a href="{{ url('/fournisseurs/index') }}">Fournisseurs </a></li>
                        <li><a href="{{ url('/cantines/index') }}">Souscriptions </a></li>



                    </ul>

                </li>
            @endif



            @if ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN)
                <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-icons">article</i>
                        <span class="nav-text">Parametres </span>
                    </a>
                    <ul aria-expanded="false">


                        <li><a href="{{ url('/utilisateurs/index') }}">Utilisateurs</a></li>
                        <li><a href="{{ url('/cycles/index') }}">Cycles </a></li>

                        <li><a href="{{ url('/niveaux/index') }}">Niveaux </a></li>
                        <li><a href="{{ url('/classes/index') }}">Classes </a></li>
                        <li><a href="{{ url('/tranches/index') }}">Tranches </a></li>

                        <li><a href="{{ url('/fraisecoles/index') }}">Type de frais </a></li>



                    </ul>
                </li>
            @endif


        </ul>

    </div>
</div>
