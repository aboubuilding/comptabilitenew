@extends('layout.app')

@section('title')
    Comptabilite | Imprimer Qr Code
@endsection

@section('titre')
    Imprimer Qr Code
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



@section('contenu')
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            <div class="card">
                <div class="card-body pb-xl-4 pb-sm-3 pb-0">

                    <form method="post" action="#" enctype="multipart/form-data" id="form">

                        @csrf

                        <div class="row">

                            <h3 class="mb-0" style="text-transform: uppercase">Choix de léleve </h3>
                            <hr>

                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label class="form-label d-block">Eleves </label>
                                    <select class="single-select col-xl-12" id="single-select" name="inscription_id">
                                        <option selected value="">Choisir un élève </option>





                                        @foreach ($inscriptions as $inscription)
                                            @if ($inscription->is_print == 0)
                                                <option value="{{ $inscription->id }}">
                                                    {{ $inscription->eleve->nom . ' ' . $inscription->eleve->prenom . ' | ' . $inscription->cycle->libelle }}</option>
                                            @endif
                                        @endforeach



                                    </select>

                                </div>

                                <span class="text-danger error-text single-select_error"> </span>

                            </div>


                        </div>

                        <br>
                        <br>

                        <hr>

                        <div class="">
                            <button class="btn btn-primary" type="button" id="imprimer">Imprimer</button>
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




            // evenement a executer apres un le clicc sur le bouton de validation

            $(document).on('click', '#imprimer', function(event) {

                event.preventDefault();

                let inscription_id = parseInt($('#single-select').val());

                if (isNaN(inscription_id) || inscription_id === 0) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Veuillez sélectionner un élève.',
                    });



                } else {
                    window.open("{{ url('/inscriptions/print') }}/" + inscription_id, '_blank');
                    location.reload();
                }

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

                let nombre = parseFloat($(this).find(':selected').data('nombre'));
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
