
@extends('layout.app')

@section('title')


    Comptabilite | Ajouter un paiement
@endsection

@section('titre')
Ajouter un paiement
@endsection

@section('css')

    <link href="{{asset('admin/css/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />

    <link rel="stylesheet" href="{{asset('admin')}}/vendor/select2/css/select2.min.css">

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

                    <form  method="post" action="#" enctype="multipart/form-data" id="form">

                        @csrf

                     <div class="row">

                <h3 class="mb-0" style="text-transform: uppercase">Information de l ' eleve </h3>
                <hr>

                <div class="col-xl-6">
                    <div class="mb-3">
                        <label  class="form-label d-block">Eleves   </label>
                        <select class="single-select col-xl-12"  id="single-select" name="inscription_id">
                            <option selected value="0">  </option>

                            @php

                                $session = session()->get('LoginUser');
                                     $annee_id = $session['annee_id'];
                                    $compte_id = $session['compte_id'];

                                     $inscriptions = \App\Models\Inscription::getListe($annee_id, null, null, null, null, null,null,\App\Types\StatutValidation::VALIDE);

                            @endphp

                            @foreach( $inscriptions  as $inscription )

                                <option value="{{$inscription->id}}" >{{$inscription->eleve->nom.' '.$inscription->eleve->prenom}}</option>


                            @endforeach

                        </select>

                    </div>

                    <span class="text-danger error-text inscription_id_error"> </span>

                </div>

                <div class="col-xl-6">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Niveau de l' élève  </label>
                        <input type="text" class="form-control" id="niveau" name="niveau" ><br>


                    </div>



                </div>

                <h5 class="mb-0">Information sur le paiement  </h5>
                <hr>
                <div class="col-xl-6">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Nom du payeur </label>
                        <input type="text" class="form-control" id="payeur" name="payeur" ><br>




                    </div>

                    <span class="text-danger error-text payeur_error"> </span>

                </div>
                <div class="col-xl-6">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Télephone payeur </label>
                        <input type="text" class="form-control" id="telephone_payeur" name="telephone_payeur" ><br>




                    </div>

                    <span class="text-danger error-text telephone_payeur_error"> </span>

                </div>
                <div class="col-xl-6">
                    <div class="mb-3">
                        <label  class="form-label d-block">Mode de paiement </label>
                        <select class="default-select col-xl-12"  id="mode_paiement" name="mode_paiement">
                            <option selected>Choisir un  mode de paiement   </option>role
                            <option value="{{\App\Types\ModePaiement::ESPECE}}">Espèces </option>
                            <option value="{{\App\Types\ModePaiement::CHEQUE}}">Chèque  </option>

                        </select>

                    </div>

                    <span class="text-danger error-text mode_paiement_error"> </span>

                </div>

                <div class="col-xl-6">
                    <div class="mb-3">
                        <label  class="form-label d-block">Montant total  </label>

                        <input type="text" disabled class="form-control" id="montant_total" name="montant_total" ><br>



                    </div>

                    <span class="text-danger error-text montant_total_error"> </span>



                </div>


                <h5 class="mb-0">Detail du paiement   </h5>
                <hr>

                <!-- Nav tabs -->
                <div class="default-tab">
                    <ul class="nav nav-tabs" role="tablist">

                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#frais"><i class="la la-user me-2"></i> Mes frais  </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link " data-bs-toggle="tab" href="#produit"><i class="la la la-user me-2"></i> Produits  </a>
                        </li>

                    </ul>
                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="frais">
                            <br>
                            <br>

                            <div class="row">

                                <div class="table-responsive full-data">
                                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="example">
                                        <thead>
                                        <tr>

                                            <th>Frais   </th>
                                            <th>Montant prévu  </th>

                                            <th>Déja  payé    </th>
                                            <th>Reste  </th>
                                            <th>Montant   </th>



                                        </tr>
                                        </thead>
                                        <tbody id="liste_frais">



                                        </tbody>
                                    </table>
                                </div>

                            </div>





                        </div>
                        <div class="tab-pane fade" id="produit">

                        </div>

                    </div>
                </div>

                         <hr>

                         <div class="">
                             <a href="{{url('/paiements/mine')}}" class="btn btn-outline-primary me-3">Annuler</a>
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
    <script src="{{asset('admin')}}/vendor/select2/js/select2.full.min.js"></script>
    <script src="{{asset('admin')}}/js/plugins-init/select2-init.js"></script>
    <script src="{{asset('admin')}}/vendor/select2/js/select2.full.min.js"></script>
    <script src="{{asset('admin')}}/js/plugins-init/select2-init.js"></script>


    <script src="{{asset('admin/js/sweetalert2/sweetalert2.min.js')}}"></script>


    <script>
        jQuery(document).ready(function() {


            //--------------------------------- changement de choix de l eleve


            $("#single-select").on("change", function (event) {

                event.preventDefault();

                let inscription_id = parseInt($('#single-select').val());

                chargerFrais(inscription_id);


            });

// evenement a executer apres un changement de la valeur saisie dans un input
            $(document).on('change', 'input.montant_a_payer', function() {

                let row = $(this).closest('tr');



                let libelle = row.find('td:eq(0)').text();
                let montant_prevue = parseFloat(row.find('td:eq(1)').text()) ;
                let montant_deja = parseFloat(row.find('td:eq(2)').text()) ;
                let reste = parseFloat(row.find('td:eq(3)').text()) ;
                let valeur_saisie = parseFloat($(this).val()) ;

                let somme = montant_deja + valeur_saisie;



                if(somme > montant_prevue){

                    alert('La valeur de'+ libelle+ ' saisie  est erronnée . la somme avec le reste depasse la somme prévue')
                }else {

                    sommePayer()
                }




            });


            // evenement a executer apres un le clicc sur le bouton de validation

            $(document).on('click', '#ajouterPaiement', function(event) {

                event.preventDefault();


                event.preventDefault();
                validerPaiement()

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
            $("#ajouterPaiement").attr("disabled", false);





        }



        //------------------------  Charger les frais de scolarite
        function chargerFrais(inscription_id) {



            $.ajax({
                dataType: 'json',
                type: 'GET',
                url: "/inscriptions/charger/" + inscription_id,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },


                data: {


                },


                success: function (data) {

                    console.log(data);


                    $('#niveau').val(data.libelle_niveau);

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

                error: function (data) {

                    console.log(data);



                }



            });


        }



        //------------------------  Charger les frais de scolarite
        function sommePayer() {


            var total = 0;
            $('input.montant_a_payer').each(function() {

                var inputValue = parseFloat($(this).val());

                if (!isNaN(inputValue)) {
                    total += inputValue;
                }

            });

            $('#montant_total').val(total);

        }




        //------------------------ Valider la creation  du paiement

        function validerPaiement() {

            let allValid = true;
            let inscription_id = parseInt($("#single-select").val(), 10);
            let mode_paiement = parseInt($("#mode_paiement").val(), 10);
            let payeur = $('#payeur').val().trim();
            let telephone_payeur = $('#telephone_payeur').val().trim();




            // Ajout des donnees de  produits
            let liste_details = [];

            let mt = 0;
            $('#liste_frais  tr').each(function () {


                var detail = {

                    type_paiement: $(this).find("td:eq(0)").attr('data-id'),
                    frais_ecole: $(this).find("td:eq(0)").text(),


                    montant : isNaN(parseFloat($(this).find(".montant_a_payer").val())) ? 0 : parseFloat($(this).find(".montant_a_payer").val()),




                }

                montant_ligne = isNaN(parseFloat($(this).find(".montant_a_payer").val())) ? 0 : parseFloat($(this).find(".montant_a_payer").val());


                mt += montant_ligne;


                liste_details.push(detail);


            });



            $('#montant_total').val(mt);

            let montant_total  = parseFloat( $('#montant_total').val());

            if(mt == 0){
                $('.montant_total_error').text("Le  montant total ne peut etre   nulle ");
                allValid = false;

            }

            if(isNaN(montant_total) ||montant_total == 0){
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


            if (payeur === '') {
                $('.payeur_error').text("Le nom du payeur    est obligatoire ");
                allValid = false;

            }


            if (telephone_payeur === '') {
                $('.telephone_payeur_error').text("Le telephone  du payeur    est obligatoire ");
                allValid = false;

            }

            if (telephone_payeur === '') {
                $('.telephone_payeur_error').text("Le telephone  du payeur    est obligatoire ");
                allValid = false;

            }



            console.log(liste_details)


            if (allValid) {

                $("#ajouterPaiement").attr("disabled", true);

                let form = document.getElementById('form');
                let formData = new FormData(form);

                if (liste_details.length) {



                    for (var i = 0; i < liste_details.length; i++) {
                        formData.append('ligne_details[' + i + '][libelle]', liste_details[i].libelle);
                        formData.append('ligne_details[' + i + '][type_paiement]', liste_details[i].type_paiement);
                        formData.append('ligne_details[' + i + '][montant]', liste_details[i].montant);

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

                    success: function (data) {


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
                                html: "Le code de paiement est " + data.paiement_reference + "<br> Le montant à payer est " + data.montant + " Frs",
                                icon: 'info',
                                text: "",
                                type: "warning",
                                showCancelButton: !0,
                                confirmButtonText: "Liste des paiements ",
                                cancelButtonText: "Nouveau paiement",
                                reverseButtons: !0
                            }).then(function (e) {

                                if (e.value === true) {


                              location.href = '/paiements/index';

                                } else {
                                    e.dismiss;

                                    location.href = '/paiements/add';
                                }

                            }, function (dismiss) {
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
