@extends('layout.app')

@section('title')
    Comptabilite | Ajouter un paiement
@endsection

@section('titre')
    Ajouter un paiement
@endsection

@php
    $user_value = session()->get('LoginUser');
    $compte_id = $user_value['compte_id'];
    $user_annee = $user_value['annee_id'];
@endphp

@section('css')
    <link href="{{ asset('admin/css/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

    <link rel="stylesheet" href="{{ asset('admin') }}/vendor/select2/css/select2.min.css">
@endsection

@section('nav')
    @include('admin.aside')
@endsection

@php
    $nbre_mois = App\Helpers\Helper::diffDate();
@endphp

@section('contenu')
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            <div class="card">
                <div class="card-body pb-xl-4 pb-sm-3 pb-0">

                    <form method="post" action="#" enctype="multipart/form-data" id="form">

                        @csrf

                        <div class="row">

                            <h3 class="mb-0" style="text-transform: uppercase">Information de l ' eleve </h3>
                            <hr>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label d-block">Eleves </label>
                                    <select class="single-select col-xl-12" id="single-select" name="inscription_id">
                                        <option selected value="0"> </option>





                                        @foreach ($inscriptions as $inscription)
                                            <option value="{{ $inscription->id }}">
                                                {{ $inscription->eleve->nom . ' ' . $inscription->eleve->prenom }}</option>
                                        @endforeach



                                    </select>

                                </div>

                                <span class="text-danger error-text single-select_error"> </span>

                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Niveau de l' élève </label>
                                    <input type="text" class="form-control" id="niveau" name="niveau"><br>


                                </div>



                            </div>

                            <h5 class="mb-0">Information sur le paiement </h5>
                            <hr>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Nom du payeur </label>
                                    <input type="text" class="form-control" id="payeur" name="payeur"><br>




                                </div>

                                <span class="text-danger error-text payeur_error"> </span>

                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Télephone payeur </label>
                                    <input type="text" class="form-control" id="telephone_payeur"
                                        name="telephone_payeur"><br>




                                </div>

                                <span class="text-danger error-text telephone_payeur_error"> </span>

                            </div>
                            <div class="col-xl-4">
                                <div class="mb-3">
                                    <label class="form-label d-block">Mode de paiement </label>
                                    <select class="default-select col-xl-12" id="mode_paiement" name="mode_paiement">
                                        <option selected>Choisir un mode de paiement </option>
                                        <option value="{{ \App\Types\ModePaiement::ESPECE }}">Espèces </option>
                                        <option value="{{ \App\Types\ModePaiement::CHEQUE }}">Chèque </option>

                                    </select>

                                </div>

                                <span class="text-danger error-text mode_paiement_error"> </span>

                            </div>
                            <div class="col-xl-4">
                                <div class="mb-3">
                                    <label class="form-label d-block">Montant total </label>

                                    <input type="text" disabled class="form-control" id="montant_total"
                                        name="montant_total"><br>



                                </div>

                                <span class="text-danger error-text montant_total_error"> </span>



                            </div>

                            <div class="col-xl-4">
                                <div class="mb-3">
                                    <label class="form-label d-block">Remise (%) </label>

                                    <input type="text" disabled class="form-control" id="taux_remise"
                                        name="taux_remise"><br>



                                </div>
                            </div>
                            <div class="row" id="is_cheque">

                                <div class="col-xl-3">
                                    <div class="mb-3">
                                        <label class="form-label d-block">Numéro chèque </label>

                                        <input type="text" class="form-control" id="num_cheque" name="num_cheque"><br>



                                    </div>

                                    <span class="text-danger error-text num_cheque_error"> </span>



                                </div>
                                <div class="col-xl-2">
                                    <div class="mb-3">
                                        <label class="form-label d-block">Montant du chèque</label>

                                        <input type="number" class="form-control" id="montant_cheque"
                                            name="montant_cheque"><br>



                                    </div>

                                    <span class="text-danger error-text montant_cheque_error"> </span>



                                </div>
                                <div class="col-xl-3">
                                    <div class="mb-3">
                                        <label class="form-label d-block">Emetteur chèque </label>

                                        <input type="text" class="form-control" id="emetteur_cheque"
                                            name="emetteur_cheque"><br>



                                    </div>

                                    <span class="text-danger error-text emetteur_cheque_error"> </span>



                                </div>
                                <div class="col-xl-2">
                                    <div class="mb-3">
                                        <label class="form-label d-block">Nom de la Banque </label>
                                        @php
                                            $banques = App\Models\Banque::getListe();
                                        @endphp

                                        <select class="single-select col-xl-12" id="banque_cheque" name="banque_cheque">
                                            <option selected value="0"> </option>





                                            @foreach ($banques as $banque)
                                                <option value="{{ $banque->id }}">
                                                    {{ $banque->nom }}</option>
                                            @endforeach



                                        </select><br>



                                    </div>

                                    <span class="text-danger error-text banque_cheque_error"> </span>



                                </div>
                                <div class="col-xl-2">
                                    <div class="mb-3">
                                        <label class="form-label d-block">Date Emission</label>

                                        <input type="date" class="form-control" id="date_cheque"
                                            name="date_cheque"><br>



                                    </div>

                                    <span class="text-danger error-text date_cheque_error"> </span>



                                </div>
                            </div>


                            

                            <input type="number" hidden value="{{ $user_annee }}" id="annee_id">


                            <h5 class="mb-0">Detail du paiement </h5>
                            <hr>

                            <!-- Nav tabs -->
                            <div class="default-tab">
                                <ul class="nav nav-tabs" role="tablist">


                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#all"><i
                                                class="la la-user me-2"></i> Tous les paiements </a>
                                    </li>


                                    <li class="nav-item">
                                        <a class="nav-link " data-bs-toggle="tab" href="#frais"><i
                                                class="la la-user me-2"></i> Scolarité </a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link " data-bs-toggle="tab" href="#cantine"><i
                                                class="la la la-user me-2"></i> Cantine </a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link " data-bs-toggle="tab" href="#bus"><i
                                                class="la la la-user me-2"></i> Bus </a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link " data-bs-toggle="tab" href="#livre"><i
                                                class="la la la-user me-2"></i> Livre </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link " data-bs-toggle="tab" href="#produit"><i
                                                class="la la la-user me-2"></i> Produits </a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link " data-bs-toggle="tab" href="#frais_examen"><i
                                                class="la la la-user me-2"></i> Frais examen </a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link " data-bs-toggle="tab" href="#activite"><i
                                                class="la la la-user me-2"></i> Activités extra scolaire </a>
                                    </li>
                                    {{-- <li class="nav-item">
                                        <a class="nav-link " data-bs-toggle="tab" href="#remise"><i
                                                class="la la la-user me-2"></i> Remise </a>
                                    </li> --}}

                                </ul>
                                <div class="tab-content">



                                    <div class="tab-pane fade show active" id="all">
                                        <br>
                                        <br>

                                        <div class="row">

                                            <div class="table-responsive full-data">
                                                <table
                                                    class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                                                    id="example">
                                                    <thead>
                                                        <tr>

                                                            <th>Reference </th>
                                                            <th>Libelle </th>
                                                            <th>Date </th>
                                                            <th>Type paiement </th>

                                                            <th>Montant </th>
                                                            <th>Payeur </th>




                                                        </tr>
                                                    </thead>
                                                    <tbody id="liste_all">



                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>





                                    </div>


                                    <div class="tab-pane fade show " id="frais">
                                        <br>
                                        <br>

                                        <div class="row">

                                            <div class="table-responsive full-data">
                                                <table
                                                    class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                                                    id="example">
                                                    <thead>
                                                        <tr>

                                                            <th>Frais </th>
                                                            <th>Montant prévu </th>

                                                            <th>Déja payé </th>
                                                            <th>Reste </th>
                                                            <th>Montant </th>



                                                        </tr>
                                                    </thead>
                                                    <tbody id="liste_frais">



                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>





                                    </div>

                                    <div class="tab-pane fade" id="cantine">

                                        <br>
                                        <br>

                                        <div class="row" id="cantine_new">

                                            <div class="col-xl-3" id="offre_liste_cantine">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Offre cantine (Prix mensuel) </label>
                                                    <select class="default-select col-xl-12" id="cantine_id"
                                                        name="cantine_id">

                                                        <option value="0"> Choisir une offre </option>



                                                        @foreach ($offres_cantine as $offre)
                                                            <option value="{{ $offre->id }}" data-nombre="10"
                                                                data-prix ="{{ $offre->montant }}">{{ $offre->libelle }}
                                                                -
                                                                {{ $offre->montant }}</option>
                                                        @endforeach

                                                    </select>

                                                </div>

                                                <span class="text-danger error-text cantine_id_error"> </span>

                                            </div>





                                            <div class="col-xl-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Montant annuel à payer </label>

                                                    <input type="text" class="form-control"
                                                        id="montant_cantine_annuel" name="montant_cantine_annuel"><br>



                                                </div>

                                                <span class="text-danger error-text montant_cantine_annuel_error"> </span>



                                            </div>


                                            <div class="col-xl-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Montant deja payé </label>

                                                    <input type="text" class="form-control" id="montant_cantine_payer"
                                                        name="montant_cantine_payer"><br>



                                                </div>

                                                <span class="text-danger error-text montant_cantine_payer_error"> </span>



                                            </div>


                                            <div class="col-xl-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Montant en cours </label>

                                                    <input type="text" class="form-control" id="montant_cantine"
                                                        name="montant_cantine"><br>



                                                </div>

                                                <span class="text-danger error-text montant_cantine_error"> </span>



                                            </div>


                                        </div>






                                    </div>

                                    <div class="tab-pane fade" id="bus">

                                        <br>
                                        <br>

                                        <div class="row" id="bus_new">

                                            <br>
                                            <br>



                                            <div class="row" id="bus_new">

                                                <div class="col-xl-3" id="offre_liste_bus">
                                                    <div class="mb-3">
                                                        <label class="form-label d-block">Offre bus (Prix moyen mensuel)
                                                        </label>
                                                        <select class="default-select col-xl-12 select2" id="bus_id"
                                                            name="bus_id">

                                                            <option value="0"> Choisir une offre </option>



                                                            @foreach ($offres_bus as $offre)
                                                                <option value="{{ $offre->id }}" data-nombre="10"
                                                                    data-prix ="{{ $offre->montant }}">
                                                                    {{ $offre->libelle }}</option>
                                                            @endforeach

                                                        </select>

                                                    </div>

                                                    <span class="text-danger error-text bus_id_error"> </span>

                                                </div>


                                                <div class="col-xl-2">
                                                    <div class="mb-3">
                                                        <label class="form-label d-block">Montant mensuel à payer </label>

                                                        <input type="number" class="form-control"
                                                            id="montant_bus_mensuel" name="montant_bus_mensuel"
                                                            oninput="montantBusMensuelChange()"><br>



                                                    </div>

                                                    <span class="text-danger error-text montant_bus_mensuel_error"> </span>

                                                </div>

                                                <input type="number" hidden value="{{ $nbre_mois }}" id="nbre_mois"
                                                    name="nbre_mois">


                                                <div class="col-xl-2">
                                                    <div class="mb-3">
                                                        <label class="form-label d-block">Montant annuel à payer </label>

                                                        <input type="text" class="form-control"
                                                            id="montant_bus_annuel" name="montant_bus_annuel"><br>



                                                    </div>

                                                    <span class="text-danger error-text montant_bus_annuel_error"> </span>

                                                </div>


                                                <div class="col-xl-2">
                                                    <div class="mb-3">
                                                        <label class="form-label d-block">Montant deja payé </label>

                                                        <input type="text" class="form-control" id="montant_bus_payer"
                                                            name="montant_bus_payer"><br>



                                                    </div>

                                                    <span class="text-danger error-text montant_bus_payer_error"> </span>



                                                </div>


                                                <div class="col-xl-3">
                                                    <div class="mb-3">
                                                        <label class="form-label d-block">Montant en cours </label>

                                                        <input type="number" class="form-control" id="montant_bus"
                                                            name="montant_bus"><br>



                                                    </div>

                                                    <span class="text-danger error-text montant_bus_error"> </span>



                                                </div>


                                            </div>

                                        </div>

                                    </div>

                                    <div class="tab-pane fade" id="livre">



                                        <div class="row" id="bus_new">

                                            <div class="col-xl-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Offre livre (Prix annuel ) </label>
                                                    <select class="default-select col-xl-12" id="livre_id"
                                                        name="livre_id">

                                                        <option value="0"> Choisir une offre </option>



                                                        @foreach ($offres_livre as $offre)
                                                            <option value="{{ $offre->id }}" data-nombre="10"
                                                                data-prix ="{{ $offre->montant }}">{{ $offre->libelle }}
                                                                - {{ $offre->montant }}</option>
                                                        @endforeach

                                                    </select>

                                                </div>

                                                <span class="text-danger error-text livre_id_error"> </span>

                                            </div>





                                            <div class="col-xl-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Montant annuel à payer </label>

                                                    <input type="text" class="form-control" id="montant_livre_annuel"
                                                        name="montant_livre_annuel"><br>



                                                </div>

                                                <span class="text-danger error-text montant_livre_annuel_error"> </span>



                                            </div>


                                            <div class="col-xl-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Montant deja payé </label>

                                                    <input type="text" class="form-control" id="montant_livre_payer"
                                                        name="montant_livre_payer"><br>



                                                </div>

                                                <span class="text-danger error-text montant_livre_payer_error"> </span>



                                            </div>


                                            <div class="col-xl-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Montant en cours </label>

                                                    <input type="text" class="form-control" id="montant_livre"
                                                        name="montant_livre"><br>



                                                </div>

                                                <span class="text-danger error-text montant_livre_error"> </span>



                                            </div>


                                        </div>

                                    </div>





                                    <div class="tab-pane fade" id="produit">



                                        <br>
                                        <br>

                                        <div class="row" id="liste_produit">

                                            <div class="col-xl-4">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Liste des produits </label>
                                                    <select class="default-select col-xl-12" id="produit_id"
                                                        name="produit_id">


                                                        <option value="0"> Choisir le produit</option>


                                                        @foreach ($produits as $produit)
                                                            <option value="{{ $produit->id }}"
                                                                data-prix_unitaire = "{{ $produit->montant }}">
                                                                {{ $produit->libelle }} - {{ $produit->montant }}</option>
                                                        @endforeach

                                                    </select>

                                                </div>

                                                <span class="text-danger error-text produit_id_error"> </span>

                                            </div>





                                            <div class="col-xl-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Quantité </label>

                                                    <input type="number" class="form-control" id="quantite_produit"
                                                        name="quantite_produit"><br>



                                                </div>

                                                <span class="text-danger error-text quantite_produit_error"> </span>



                                            </div>



                                            <div class="col-xl-4">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Montant à payer </label>

                                                    <input type="number" class="form-control" id="montant_ligne_produit"
                                                        name="montant_ligne_produit"><br>



                                                </div>

                                                <span class="text-danger error-text montant_ligne_produit_error"> </span>



                                            </div>


                                            <hr>
                                            <div class="text-center">
                                                <a class="btn btn-primary" id="ajouterProduit">Ajouter</a>
                                                <button class="btn btn-danger light ms-1" id="annulerProduit">Annuler
                                                </button>
                                            </div>


                                        </div>


                                        <strong>Liste des produits </strong>
                                        <hr>

                                        <div class="table-responsive active-projects user-tbl  dt-filter">
                                            <table id="user-tbl" class="table shorting">
                                                <thead>
                                                    <tr>

                                                        <th>Produit </th>
                                                        <th>Prix unitaire </th>

                                                        <th>Quantite </th>

                                                        <th>Total </th>

                                                        <th style="width: 10%">Action</th>

                                                    </tr>
                                                </thead>
                                                <tbody id="list_produit">



                                                </tbody>

                                            </table>
                                        </div>




                                    </div>




                                    <div class="tab-pane fade" id="frais_examen">



                                        <br>
                                        <br>


                                        <div id="row_examen" class="row">

                                            <div class="col-xl-4">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Frais examen </label>

                                                    <input type="number" class="form-control" id="montant_frais_examen"
                                                        name="montant_frais_examen"><br>


                                                </div>

                                                <span class="text-danger error-text montant_frais_examen_error"> </span>



                                            </div>

                                            <div class="col-xl-4">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Frais déjà payé </label>

                                                    <input type="number" class="form-control" id="deja_frais_examen"
                                                        name="deja_frais_examen"><br>



                                                </div>

                                                <span class="text-danger error-text deja_frais_examen_error"> </span>



                                            </div>

                                            <div class="col-xl-4">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Montant à payer </label>

                                                    <input type="number" class="form-control" id="frais_examen_payer"
                                                        name="frais_examen_payer"><br>



                                                </div>

                                                <span class="text-danger error-text frais_examen_payer_error"> </span>



                                            </div>


                                        </div>










                                    </div>







                                </div>

                                <div class="tab-pane fade" id="activite">

                                </div>
                                {{-- <div class="tab-pane fade" id="remise">
                                    <br><br>
                                    <div class="col-xl-12">
                                        <div class="mb-3">
                                            <label class="form-label d-block">Taux de remise</label>
                                            <select class="single-select col-xl-12" id="choix_remise"
                                                name="choix_remise">
                                                <option value="">Choisir le taux de remise</option>
                                                <option value="0">0%</option>
                                                <option value="5">5%</option>
                                                <option value="10">10%</option>
                                                <option value="15">15%</option>
                                                <option value="20">20%</option>
                                            </select>

                                        </div>
                                    </div>
                                </div> --}}

                            </div>
                        </div>


                        <br>
                        <br>
                        <br>
                        <br>

                        <hr>

                        <div class="">
                            <a href="{{ url('/paiements/mine') }}" class="btn btn-outline-primary me-3">Annuler</a>
                            <button class="btn btn-primary" type="button" id="ajouterPaiement">Valider </button>
                        </div>






                </div>

                </form>

            </div>
        </div>




    </div>
    </div>
@endsection





@section('js')
    <!-- Datatable -->
    <script src="{{ asset('admin') }}/vendor/select2/js/select2.full.min.js"></script>
    <script src="{{ asset('admin') }}/js/plugins-init/select2-init.js"></script>
    <script src="{{ asset('admin') }}/vendor/select2/js/select2.full.min.js"></script>
    <script src="{{ asset('admin') }}/js/plugins-init/select2-init.js"></script>


    <script src="{{ asset('admin/js/sweetalert2/sweetalert2.min.js') }}"></script>


    <script>
        jQuery(document).ready(function() {


            //--------------------------------- Gestion de l onglet scolarite



            //--------------------------------- changement de choix de l eleve


            $("#single-select").on("change", function(event) {

                event.preventDefault();

                let inscription_id = $('#single-select').val();




                chargerFrais(inscription_id);

                chargerPaiement(inscription_id);


            });





            // evenement a executer apres un changement de la valeur saisie dans un input
            $(document).on('change', 'input.montant_a_payer', function() {

                let row = $(this).closest('tr');



                let libelle = row.find('td:eq(0)').text();
                let montant_prevue = parseFloat(row.find('td:eq(1)').text());
                let montant_deja = parseFloat(row.find('td:eq(2)').text());
                let reste = parseFloat(row.find('td:eq(3)').text());
                let valeur_saisie = parseFloat($(this).val());
                // let taux_remise = parseInt($('#taux_remise').val());

                let somme = montant_deja + valeur_saisie;

                // let reste = montant_prevue - montant_prevue*taux_remise/100 -montant_deja;

                if (valeur_saisie > reste) {

                    // if (somme > (montant_prevue - montant_prevue*taux_remise/100)) {

                    alert('La valeur de ' + libelle +
                        ' saisie  est erronnée . La somme ne doit pas depasser ' + reste + ' Frs');

                } else {

                    sommePayer();
                }




            });


            // evenement a executer apres un le clicc sur le bouton de validation

            $(document).on('click', '#ajouterPaiement', function(event) {

                event.preventDefault();


                event.preventDefault();
                validerPaiement()

            });



            //--------------------------------- Gestion de l onglet  des produits

            //------------------------ changement de produit


            $("#produit_id").on("change", function() {


                event.preventDefault();

                let prix_unitaire = $(this).find(':selected').data('prix_unitaire');

                let produit_id = parseInt($('#produit_id').val());
                let quantite_produit = parseInt($('#quantite_produit').val());
                if (isNaN(quantite_produit) || quantite_produit == 0)

                {

                    $('.quantite_produit_error').text("Le  quantite de produit ne doit pas etre nulle  ");

                } else {


                    let montant_ligne_produit = prix_unitaire * quantite_produit;

                    $('#montant_ligne_produit').val(montant_ligne_produit);


                }

            });



            //------------------------ changement de QUANTITE DE produit


            $("#quantite_produit").on("change", function() {


                event.preventDefault();

                let prix_unitaire = $('#produit_id').find(':selected').data('prix_unitaire');

                let produit_id = parseInt($('#produit_id').data());
                let quantite_produit = parseInt($('#quantite_produit').val());
                if (isNaN(quantite_produit) || quantite_produit == 0)

                {

                    $('.quantite_produit_error').text("Le  quantite de produit ne doit pas etre nulle  ");

                } else {


                    let montant_ligne_produit = prix_unitaire * quantite_produit;

                    $('#montant_ligne_produit').val(montant_ligne_produit);


                }

            });



            //------------------------ Ajouter produit à la liste

            $("#ajouterProduit").click(function(event) {
                event.preventDefault();

                ajouterProduit()
            });



            //------------------- Supprimer une ligne de produit ajoutée dans le modal

            $("#list_produit").on("click", ".supprimer", function() {



                var ligneASupprimer = $(this).closest("tr");


                ligneASupprimer.remove();
            });


            //------------------Affectation de taux de remise

            $("#choix_remise").on("change", function(event) {

                let inscription_id = parseInt($('#single-select').val());

                let taux_remise = parseInt($('#choix_remise').val());

                if (inscription_id !== 0) {

                    $.ajax({
                        dataType: 'json',
                        type: 'POST',
                        url: "{{ route('admin_inscriptions_remise') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            inscription_id: inscription_id,
                            taux_remise: taux_remise
                        },
                        success: function(data) {
                            if (data.code == 0) {
                                $('#taux_remise').val(taux_remise);
                                $('#choix_remise').val("");
                                Swal.fire({
                                    position: 'top-end',
                                    icon: 'success',
                                    title: data.msg,
                                    showConfirmButton: false,
                                });
                            }

                        },
                        error: function(data) {

                            console.log(data)


                        }



                    });

                }

            });



            //------------------------ Annuler un produit

            $("#annulerProduit").click(function(event) {
                event.preventDefault();

                annulerProduit()
            });


            // mode_paiement change

            $("#mode_paiement").on("change", function() {

                event.preventDefault();

                let mode_paiement = parseInt($('#mode_paiement').val());

                if (mode_paiement == 2) {

                    $("#is_cheque").show();

                } else {
                    $('#num_cheque').val('');
                    $('#banque_cheque').val('');
                    $('#date_cheque').val('');
                    $("#is_cheque").hide();
                }


            });

            //--------------------------------- Gestion de l onglet  cantine


            //------------------------ changement d offre de cantine


            $("#cantine_id").on("change", function() {


                event.preventDefault();

                // let nombre = parseFloat($(this).find(':selected').data('nombre'));
                let nombre  = parseInt($("#nbre_mois").val());
                let prix_mensuel = parseFloat($(this).find(':selected').data('prix'));

                let cantine_id = parseInt($('#cantine_id').val());


                let montant_cantine_annuel = prix_mensuel * nombre;

                $('#montant_cantine_annuel').val(montant_cantine_annuel);

                $('#montant_cantine').prop('disabled', false);
                $('#montant_cantine_annuel').prop('disabled', false);


            });


            $("#montant_cantine").on("change", function() {


                event.preventDefault();
                let annee = parseInt($('#annee_id').val());
                let montant_cantine_annuel = parseFloat($('#montant_cantine_annuel').val());
                let montant_cantine_payer = parseFloat($('#montant_cantine_payer').val());
                let montant_cantine = parseFloat($('#montant_cantine').val());
                let demi_reste = (montant_cantine_annuel - montant_cantine_payer) / 2;

                if ((montant_cantine + montant_cantine_payer) > montant_cantine_annuel) {

                    $('.montant_cantine_error').text(
                        "Le montant saisie est erronnée. La somme avec le montant dejà payé est superieur au montant annuel à payer  "
                    );

                    $('#montant_cantine').val('');


                } else if (montant_cantine < demi_reste && annee != 1) {
                    $('.montant_cantine_error').text(
                        "Vous devez entrer le montant supérieur à la moitié du reste à payer (" +
                        demi_reste + " frs)"
                    );
                    $('#montant_cantine').val('');
                } else {

                    $('.montant_cantine_error').text("");

                    sommePayer()

                }

            });



            //--------------------------------- Gestion de l onglet  bus


            //------------------------ changement d offre de bus


            $("#bus_id").on("change", function() {


                event.preventDefault();

                let nombre = parseFloat($(this).find(':selected').data('nombre'));
                let prix_mensuel = parseFloat($(this).find(':selected').data('prix'));

                let bus_id = parseInt($('#bus_id').val());



                // let montant_bus_annuel = prix_mensuel * nombre;

                // $('#montant_bus_annuel').val(montant_bus_annuel);

                $('#montant_bus').prop('disabled', false);
                $('#montant_bus_annuel').prop('disabled', false);

            });


            $("#montant_bus").on("change", function() {


                event.preventDefault();
                let annee = parseInt($('#annee_id').val());
                let montant_bus_annuel = parseFloat($('#montant_bus_annuel').val());
                let montant_bus_payer = parseFloat($('#montant_bus_payer').val());
                let montant_bus = parseFloat($('#montant_bus').val());
                demi_reste = (montant_bus_annuel - montant_bus_payer) / 2;

                if ((montant_bus + montant_bus_payer) > montant_bus_annuel) {

                    $('.montant_bus_error').text(
                        "Le montant saisie est erronnée. La somme avec le montant dejà payé est superieur au montant annuel à payer  "
                    );

                    $('#montant_bus').val('');
                } else if (montant_bus < demi_reste && annee != 1) {
                    $('.montant_bus_error').text(
                        "Vous devez entrer le montant supérieur à la moitié du reste à payer (" +
                        demi_reste + " frs)"
                    );
                    $('#montant_bus').val('');
                } else {

                    $('.montant_bus_error').text("");

                    sommePayer()


                }



            });





            //--------------------------------- Gestion de l onglet  livre


            //------------------------ changement d offre de livre


            $("#livre_id").on("change", function() {


                event.preventDefault();

                let nombre = parseFloat($(this).find(':selected').data('nombre'));
                let prix_mensuel = parseFloat($(this).find(':selected').data('prix'));

                let livre_id = parseInt($('#livre_id').val());



                let montant_livre_annuel = prix_mensuel * nombre;

                $('#montant_livre_annuel').val(montant_livre_annuel);

                $('#montant_livre').prop('disabled', false);
                $('#montant_livre_annuel').prop('disabled', false);




            });


            $("#montant_livre").on("change", function() {


                event.preventDefault();

                let montant_livre_annuel = parseFloat($('#montant_livre_annuel').val());
                let montant_livre_payer = parseFloat($('#montant_livre_payer').val());
                let montant_livre = parseFloat($('#montant_livre').val());

                if ((montant_livre + montant_livre_payer) > montant_livre_annuel) {

                    $('.montant_livre_error').text(
                        "Le montant saisie est erronnée. La somme avec le montant dejà payé est superieur au montant annuel à payer  "
                    );


                } else {

                    $('.montant_livre_error').text("");

                    sommePayer()


                }



            });


            //--------------------------------- Gestion de l onglet  des frais d examens




            $("#frais_examen_payer").on("change", function() {


                event.preventDefault();

                let montant_frais_examen = parseFloat($('#montant_frais_examen').val());
                let deja_frais_examen = parseFloat($('#deja_frais_examen').val());
                let frais_examen_payer = parseFloat($('#frais_examen_payer').val());

                if ((deja_frais_examen + frais_examen_payer) > montant_frais_examen) {

                    $('.frais_examen_payer_error').text(
                        "Le montant saisie est erronnée. La somme avec le montant dejà payé est superieur au montant des frais d' examen  à payer  "
                    );


                } else {

                    $('.frais_examen_payer_error').text("");

                    sommePayer()


                }



            });



            clearData()

            sommePayer()

        });







        function clearData() {

            $('#payeur').val('');
            $('#niveau').val('');

            $('#mode_paiement').val('');
            $('#telephone_payeur').val('');
            $('#single-select').val('');
            $('#montant_total').val(0);


            $('#num_cheque').val('');
            $('#banque_cheque').val('');
            $('#date_cheque').val('');
            $('#montant_cheque').val('');

            $("#is_cheque").hide();


            //------------------------ Initialisation des données de l onglet cantine
            $('#montant_cantine').val(0);
            $('#montant_cantine_payer').val(0);
            $('#montant_cantine_annuel').val(0);
            $('#cantine_id').val(0);


            $('#montant_cantine').prop('disabled', true);
            $('#montant_cantine_payer').prop('disabled', true);
            $('#montant_cantine_annuel').prop('disabled', true);

            //------------------------ Initialisation des données de l onglet bus

            $('#montant_bus').val(0);
            $('#montant_bus_payer').val(0);
            $('#montant_bus_annuel').val(0);
            $('#bus_id').val(0);


            $('#montant_bus').prop('disabled', true);
            $('#montant_bus_payer').prop('disabled', true);
            $('#montant_bus_annuel').prop('disabled', true);




            //------------------------ Initialisation des données de l onglet livre

            $('#montant_livre').val(0);
            $('#montant_livre_payer').val(0);
            $('#montant_livre_annuel').val(0);
            $('#livre_id').val(0);


            $('#montant_livre').prop('disabled', true);
            $('#montant_livre_payer').prop('disabled', true);
            $('#montant_livre_annuel').prop('disabled', true);



            //------------------------ Initialisation des données de l onglet produit

            $('#produit_id').val(0);
            $('#montant_ligne_produit').val(0);
            $('#quantite_produit').val(1);

            $('#montant_ligne_produit').prop('disabled', true);
            $("#ajouterPaiement").attr("disabled", false);


            //------------------------ Initialisation des données de l onglet de frais d examen


            $('#montant_frais_examen').val(0);
            $('#deja_frais_examen').val(0);
            $('#frais_examen_payer').val(0);



            $('#montant_frais_examen').prop('disabled', true);
            $('#deja_frais_examen').prop('disabled', true);
            $('#frais_examen_payer').prop('disabled', true);




        }




        //------------------------ Reset des champs lors de l annulation de l ajout de produit
        function annulerProduit() {

            clearProduit()

        }

        //----------------------- montantBusMensuelChange

        function montantBusMensuelChange() {
            // let montant = parseInt(document.getElementById("montant").value);
            let montant_bus_mensuel = parseInt($("#montant_bus_mensuel").val());
            let nbre_mois = parseInt($("#nbre_mois").val());
            let montant_bus = montant_bus_mensuel * nbre_mois;
            $('#montant_bus_annuel').val(montant_bus);
        }

        //------------------------ Ajouter  produit au paiement

        function ajouterProduit() {


            let allValid = true;



            // Recuperation des données du paiements

            let produit_id = parseInt($("#produit_id").val());

            let prix_unitaire = $('#produit_id').find(':selected').data('prix_unitaire');
            let quantite_produit = parseInt($("#quantite_produit").val());

            let libelle_produit = $("#produit_id option:selected").text();
            let total_produit = prix_unitaire * quantite_produit;

            // verification des données du formulaires

            if (isNaN(produit_id) || produit_id === 0) {
                $('.produit_id_error').text("Le choix du produit     est   obligatoire ");
                allValid = false;

            }


            if (isNaN(prix_unitaire) || prix_unitaire === 0) {
                $('.prix_unitaire_error').text("Le prix unitaire est   obligatoire ");
                allValid = false;

            }


            if (isNaN(quantite_produit) || quantite_produit === 0) {
                $('.quantite_produit_error').text("La quantite de produit  est   obligatoire ");
                allValid = false;

            }






            if (allValid) {

                let output = '';
                let index = 0;


                index += 1;

                output += `
        <tr >

        <td data-id="${produit_id}">${libelle_produit}</td>
        <td>${prix_unitaire}</td>
          <td>${quantite_produit}</td>

           <td>${total_produit}</td>

        <td style="text-align: center;"><a href="#"><i class="fa fa-trash supprimer" id="${index}" ></i></a></td>
        </tr>
        `;



                $('#list_produit').append(output);


                sommePayer();

                clearProduit()



            }
        }



        function clearProduit() {




            $('#quantite_produit').val(1);
            $('#produit_id').val(0);
            $('#montant_ligne_produit').val(0);

            $('.quantite_produit_error').text('');

            $('.produit_id_error').text('');

        }


        //------------------------  Charger les frais de scolarite
        function chargerFrais(inscription_id) {



            $.ajax({
                dataType: 'json',
                type: 'GET',
                url: "/inscriptions/charger/" + inscription_id,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },


                data: {


                },


                success: function(data) {

                    console.log(data);

                    // chargement des frais d examens

                    let montant_frais_examen = data.frais_examen;
                    let deja_frais_examen = data.montant_examen_paye;

                    if (deja_frais_examen < montant_frais_examen) {
                        $('#frais_examen_payer').prop('disabled', false);

                    }

                    // chargement des frais de cantine

                    let montant_cantine = data.frais_cantine;
                    let deja_frais_cantine = data.montant_cantine_paye;

                    if (montant_cantine) {


                        $("#offre_liste_cantine").hide();

                        $('#montant_cantine_annuel').val(montant_cantine);
                        $('#montant_cantine_payer').val(deja_frais_cantine);

                        $('#montant_cantine_annuel').prop('disabled', true);
                        $('#montant_cantine_payer').prop('disabled', true);
                        $('#montant_cantine').prop('disabled', false);


                    }


                    // chargement des frais de bus


                    let montant_bus = data.frais_bus;
                    let deja_frais_bus = data.montant_bus_paye;
                    let nbre_mois = data.nbre_mois;

                    if (montant_bus) {


                        $("#offre_liste_bus").hide();
                        $('#montant_bus_mensuel').val(montant_bus / nbre_mois);
                        $('#montant_bus_annuel').val(montant_bus);
                        $('#montant_bus_payer').val(deja_frais_bus);

                        $('#montant_bus_mensuel').prop('disabled', true);
                        $('#montant_bus_annuel').prop('disabled', true);
                        $('#montant_bus_payer').prop('disabled', true);
                        $('#montant_bus').prop('disabled', false);


                    }


                    $('#niveau').val(data.libelle_niveau);
                    $('#montant_frais_examen').val(data.frais_examen);
                    $('#deja_frais_examen').val(data.montant_examen_paye);
                    $('#taux_remise').val(data.taux_remise);
                    $('#choix_remise').val(data.taux_remise);

                    $('#liste_frais').empty();
                    let output = '';
                    let index = 0;
                    let frais = data.data;

                    for (let i = 0; i < frais.length; i++) {
                        index += 1;
                        output += `
                            <tr >

                            <td data-id="${frais[i].type_paiement}">${frais[i].libelle}</td>
                            <td>${frais[i].montant_prevu}</td>
                            <td>${frais[i].montant_deja}</td>
                            <td>${frais[i].reste}</td>

                                <td>



<input type="number" class="form-control montant_a_payer"  data-id="${frais[i].type_paiement}">



                            </td>




                            `;

                    }


                    $('#liste_frais').append(output);

                },

                error: function(data) {

                    console.log(data);



                }



            });


        }



        //------------------------  Charger tous les paiements
        function chargerPaiement(inscription_id) {



            $.ajax({
                dataType: 'json',
                type: 'GET',
                url: "/inscriptions/paiements/" + inscription_id,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },


                data: {


                },


                success: function(data) {

                    console.log(data);




                    $('#liste_all').empty();
                    let output = '';
                    let index = 0;
                    let frais = data.data;

                    for (let i = 0; i < frais.length; i++) {
                        index += 1;
                        output += `
                            <tr >

                            <td data-id="${frais[i].id}">${frais[i].reference}</td>
                            <td>${frais[i].libelle}</td>
                            <td>${frais[i].date_paiement}</td>

                            <td>${frais[i].type_paiement}</td>
                            <td>${frais[i].montant}</td>

                             <td>${frais[i].payeur}</td>




                            `;

                    }


                    $('#liste_all').append(output);

                },

                error: function(data) {

                    console.log(data);



                }



            });


        }

        //------------------------  Charger les frais de scolarite
        function sommePayer() {


            let total = 0;
            let total_detail = 0;


            //------------------------  Frais cantine
            let montant_cantine = parseFloat($('#montant_cantine').val().trim());


            //------------------------  Frais bus
            let montant_bus = parseFloat($('#montant_bus').val().trim());

            //------------------------  Frais livres
            let montant_livre = parseFloat($('#montant_livre').val().trim());



            //------------------------  Frais d 'examens '
            let frais_examen_payer = parseFloat($('#frais_examen_payer').val().trim());

            //------------------------  Frais  de scolarite
            $('input.montant_a_payer').each(function() {

                var inputValue = parseFloat($(this).val());

                if (!isNaN(inputValue)) {
                    total_detail += inputValue;
                }

            });



            //------------------------  Frais  ddes produits

            let montant_produits = 0;
            $('#list_produit  tr').each(function() {



                montant_ligne_produit = parseFloat($(this).find("td:eq(3)").text());


                montant_produits += montant_ligne_produit;


            });


            total = total_detail + montant_cantine + montant_produits + montant_livre + montant_bus + frais_examen_payer;

            $('#montant_total').val(total);

        }




        //------------------------ Valider la creation  du paiement

        function validerPaiement() {



            let allValid = true;
            let inscription_id = parseInt($("#single-select").val(), 10);

            let mode_paiement = parseInt($("#mode_paiement").val(), 10);
            let banque_cheque = parseInt($("#banque_cheque").val(), 10);
            let num_cheque = $('#num_cheque').val().trim();
            let date_cheque = $('#date_cheque').val().trim();
            let emetteur_cheque = $('#emetteur_cheque').val().trim();
            let montant_cheque = parseFloat($('#montant_cheque').val().trim());

            let frais_ecole_id = parseInt($("#frais_ecole_id").val(), 10);
            let payeur = $('#payeur').val().trim();
            let montant_cantine = parseFloat($('#montant_cantine').val().trim());
            let montant_bus = parseFloat($('#montant_bus').val().trim());
            let montant_livre = parseFloat($('#montant_livre').val().trim());
            let montant_total = parseFloat($('#montant_total').val().trim());

            let telephone_payeur = $('#telephone_payeur').val().trim();


            // Ajout des donnees de  scolarites
            let liste_details = [];

            let mt = 0;

            $('#liste_frais  tr').each(function() {


                var detail = {

                    type_paiement: $(this).find("td:eq(0)").attr('data-id'),
                    libelle: $(this).find("td:eq(0)").text(),


                    montant: isNaN(parseFloat($(this).find(".montant_a_payer").val())) ? 0 : parseFloat($(this)
                        .find(".montant_a_payer").val()),




                }



                liste_details.push(detail);


            });


            // Ajout des donnees de  produits
            let list_produit = [];


            let montant_produits = 0;
            $('#list_produit  tr').each(function() {


                var detail_produit = {

                    produit_id: $(this).find("td:eq(0)").attr('data-id'),
                    produit_name: $(this).find("td:eq(0)").text(),
                    quantite: $(this).find("td:eq(2)").text(),

                    montant: $(this).find("td:eq(3)").text(),


                }



                list_produit.push(detail_produit);


            });



            if (montant_total == 0) {
                $('.montant_total_error').text("Le  montant total ne peut etre   nulle ");
                allValid = false;

            }


            if (isNaN(inscription_id) || inscription_id === 0) {
                $('.inscription_id_error').text("Le choix de l eleve     est obligatoire ");
                allValid = false;

            }

            if (isNaN(mode_paiement) || mode_paiement === 0) {
                $('.mode_paiement_error').text("Le choix du mode paiement      est obligatoire ");
                allValid = false;

            }

            if (mode_paiement === 2) {

                if (isNaN(banque_cheque) || banque_cheque === 0) {
                    $('.banque_cheque_error').text("Le choix de la banque     est obligatoire ");
                    allValid = false;

                }

                if (num_cheque === '') {
                    $('.num_cheque_error').text("Le numero du cheque     est obligatoire ");
                    allValid = false;

                }

                if (emetteur_cheque === '') {
                    $('.emetteur_cheque_error').text("L'emetteur du cheque     est obligatoire ");
                    allValid = false;

                }

                if (date_cheque === '') {
                    $('.date_cheque_error').text("La date du cheque     est obligatoire ");
                    allValid = false;

                }
                if (isNaN(montant_cheque) || montant_cheque === 0) {
                    $('.montant_cheque_error').text("Le montant du cheque     est obligatoire ");
                    allValid = false;

                }


            }


            if (payeur === '') {
                $('.payeur_error').text("Le nom du payeur    est obligatoire ");
                allValid = false;

            }


            if (telephone_payeur === '') {
                $('.telephone_payeur_error').text("Le telephone  du payeur    est obligatoire ");
                allValid = false;

            }





            if (allValid) {

                $("#ajouterPaiement").attr("disabled", true);



                let form = document.getElementById('form');
                let formData = new FormData(form);

                formData.append('montant_total', montant_total);

                // Ajout du paiement des frais de scolarité     a formData

                if (liste_details.length) {



                    for (var i = 0; i < liste_details.length; i++) {
                        formData.append('ligne_details[' + i + '][libelle]', liste_details[i].libelle);
                        formData.append('ligne_details[' + i + '][type_paiement]', liste_details[i].type_paiement);
                        formData.append('ligne_details[' + i + '][montant]', liste_details[i].montant);

                    }

                }


                // Ajout des la liste des produits     a formData

                if (list_produit.length) {



                    for (var i = 0; i < list_produit.length; i++) {
                        formData.append('ligne_produits[' + i + '][produit_id]', list_produit[i].produit_id);
                        formData.append('ligne_produits[' + i + '][produit_name]', list_produit[i].produit_name);
                        formData.append('ligne_produits[' + i + '][montant]', list_produit[i].montant);
                        formData.append('ligne_produits[' + i + '][quantite]', list_produit[i].quantite);



                    }

                }


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/paiements/save",
                    method: $(form).attr('method'),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        // setting a timeout
                        $(form).find('span.error-text').text('');

                    },

                    success: function(data) {


                        console.log(data)

                        if (data.code === 0) {
                            // $.each(data.error, function (prefix, val) {
                            //     $(form).find('span.' + prefix + '_error').text(val[0]);
                            // });
                            Swal.fire({
                                position: 'top-end',
                                icon: 'warning',
                                title: data.msg,
                                showConfirmButton: false,


                            });
                        } else if (data.code === 2) {
                            Swal.fire({
                                position: 'top-end',
                                icon: 'warning',
                                title: data.msg,
                                showConfirmButton: false,


                            });
                        } else {

                            clearData()

                            Swal.fire({
                                html: "Le code de paiement est " + data.paiement_reference +
                                    "<br> Le montant à payer est " + data.montant + " Frs",
                                icon: 'info',
                                text: "",
                                type: "warning",
                                showCancelButton: !0,
                                confirmButtonText: "Liste des paiements ",
                                cancelButtonText: "Nouveau paiement",
                                reverseButtons: !0
                            }).then(function(e) {

                                if (e.value === true) {


                                    location.href = '/paiements/index';

                                } else {
                                    e.dismiss;

                                    location.href = '/paiements/add';
                                }

                            }, function(dismiss) {
                                return false;
                            });

                        }

                    },

                    error: function(data) {

                        console.log(data);



                    }



                });



            }







        }
    </script>
@endsection
