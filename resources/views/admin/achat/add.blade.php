
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
                        <label  class="form-label d-block">Fournisseur   </label>
                        <select class="single-select col-xl-12"  id="fournisseur_id" name="fournisseur_id">
                            <option selected value="0">  </option>

                            @php



                            @foreach( $fournisseurs  as $fournisseur )

                                <option value="{{$fournisseur->id}}" >{{$fournisseur->raison_social }}</option>


                            @endforeach

                            @endphp

                        </select>

                    </div>

                    <span class="text-danger error-text fournisseur_id_error"> </span>

                </div>


                <div class="col-xl-6">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Acheteur  </label>
                        <input type="text" class="form-control" id="nom_acheteur" name="nom_acheteur" ><br>

                          <span class="text-danger error-text nom_acheteur_error"> </span>


                    </div>



                </div>

                <div class="col-xl-6">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Date d achat  </label>
                        <input type="date" class="form-control" id="date_achat" name="date_achat" ><br>


                    </div>



                </div>


                <div class="col-xl-6">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Bon de commande    </label>
                        <input type="text" class="form-control" id="bon_commande" name="bon_commande" ><br>




                    </div>


                </div>

                <div class="col-xl-6">
                    <div class="mb-3">
                        <label  class="form-label d-block">Montant total  </label>

                        <input type="text" disabled class="form-control" id="montant_total" name="montant_total" ><br>



                    </div>

                    <span class="text-danger error-text montant_total_error"> </span>



                </div>


                <div class="col-xl-6">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Commentaire    </label>
                        <textarea type="text" class="form-control" id="commentaire" name="commentaire" >

                        </textarea><br>




                    </div>


                </div>



                <h5 class="mb-0">Details achat   </h5>
                <hr>

                <!-- Nav tabs -->
                <div class="default-tab">
                    <ul class="nav nav-tabs" role="tablist">



                        <li class="nav-item">
                            <a class="nav-link " data-bs-toggle="tab" href="#produits"><i class="la la la-user me-2"></i> Produits   </a>
                        </li>



                    </ul>
                    <div class="tab-content">




                        <div class="tab-pane fade show active" id="produit">



                            <br>
                            <br>

                            <div class="row" id="liste_produit">

                                <div class="col-xl-4">
                                    <div class="mb-3">
                                        <label  class="form-label d-block">Liste des produits  </label>
                                        <select class="default-select col-xl-12"  id="produit_id" name="produit_id">


                                            <option value="0"> Choisir le produit</option>


                            @foreach( $produits  as $produit )

                                <option value="{{$produit->id}}" data-prix_unitaire = "{{$produit->montant}}">  {{$produit->libelle }} - {{  $produit->montant}}</option>


                            @endforeach

                                        </select>

                                    </div>

                                    <span class="text-danger error-text produit_id_error"> </span>

                                </div>





                            <div class="col-xl-3">
                                <div class="mb-3">
                                    <label  class="form-label d-block">Quantité   </label>

                                    <input type="number"  class="form-control" id="quantite_produit" name="quantite_produit" ><br>



                                </div>

                                <span class="text-danger error-text quantite_produit_error"> </span>



                            </div>



                            <div class="col-xl-4">
                                <div class="mb-3">
                                    <label  class="form-label d-block">Montant à payer    </label>

                                    <input type="number"  class="form-control" id="montant_ligne_produit" name="montant_ligne_produit" ><br>



                                </div>

                                <span class="text-danger error-text montant_ligne_produit_error"> </span>



                            </div>


                            <hr>
                            <div class="text-center">
                                <a class="btn btn-primary" id="ajouterProduit">Ajouter</a>
                                <button class="btn btn-danger light ms-1" id="annulerProduit">Annuler </button>
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





                    </div>
                </div>


                <br>
                <br>
                <br>
                <br>



                         <div class="">
                             <a href="{{url('/achats/index')}}" class="btn btn-outline-primary me-3">Annuler</a>
                             <button class="btn btn-primary" type="button" id="ajouterAchat">Valider </button>
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





            // evenement a executer apres un le clicc sur le bouton de validation

            $(document).on('click', '#ajouterAchat', function(event) {

                event.preventDefault();


                event.preventDefault();
                validerAchat()

            });



  //--------------------------------- Gestion de l onglet  des produits

    //------------------------ changement de produit


    $("#produit_id").on("change", function () {


        event.preventDefault();

        let  prix_unitaire = $(this).find(':selected').data('prix_unitaire');

        let produit_id = parseInt($('#produit_id').val());
        let quantite_produit = parseInt($('#quantite_produit').val());
        if(isNaN(quantite_produit) || quantite_produit == 0 )

        {

            $('.quantite_produit_error').text("Le  quantite de produit ne doit pas etre nulle  ");

        }else {


            let montant_ligne_produit = prix_unitaire * quantite_produit;

        $('#montant_ligne_produit').val(montant_ligne_produit);


        }

    });



        //------------------------ changement de QUANTITE DE produit


    $("#quantite_produit").on("change", function () {


        event.preventDefault();

        let  prix_unitaire = $('#produit_id').find(':selected').data('prix_unitaire');

        let produit_id = parseInt($('#produit_id').data());
        let quantite_produit = parseInt($('#quantite_produit').val());
        if(isNaN(quantite_produit) || quantite_produit == 0 )

        {

            $('.quantite_produit_error').text("Le  quantite de produit ne doit pas etre nulle  ");

        }else {


            let montant_ligne_produit = prix_unitaire * quantite_produit;

        $('#montant_ligne_produit').val(montant_ligne_produit);


        }

    });



    //------------------------ Ajouter produit à la liste

    $("#ajouterProduit").click(function (event) {
        event.preventDefault();

        ajouterProduit()
    });



    //------------------- Supprimer une ligne de produit ajoutée dans le modal

    $("#list_produit").on("click", ".supprimer", function () {



        var ligneASupprimer = $(this).closest("tr");


        ligneASupprimer.remove();
    });



    //------------------------ Annuler un produit

    $("#annulerProduit").click(function (event) {
        event.preventDefault();

        annulerProduit()
    });




            clearData()

            sommePayer()

        });







        function clearData() {

            $('#nom_acheteur').val('');


            $('#bon_commande').val('');
            $('#commentaire').val('');
            $('#date_achat').val('');
            $('#fournisseur_id').val('');
            $('#montant_total').val(0);



            //------------------------ Initialisation des données de l onglet produit

            $('#produit_id').val(0);
            $('#montant_ligne_produit').val(0);
            $('#quantite_produit').val(1);

            $('#montant_ligne_produit').prop('disabled', true);
            $("#ajouterAchat").attr("disabled", false);





        }




//------------------------ Reset des champs lors de l annulation de l ajout de produit
function annulerProduit() {

    clearProduit()

}

//------------------------ Ajouter  produit au paiement

function ajouterProduit() {


    let allValid = true;



    // Recuperation des données du paiements

    let produit_id = parseInt($("#produit_id").val());

    let  prix_unitaire = $('#produit_id').find(':selected').data('prix_unitaire');
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
        function sommePayer() {


            let total = 0;
            let total_detail = 0;





             //------------------------  Frais  ddes produits

             let montant_produits = 0;
    $('#list_produit  tr').each(function () {



        montant_ligne_produit = parseFloat($(this).find("td:eq(3)").text()) ;


        montant_produits += montant_ligne_produit;


    });


            total = total_detail;

            $('#montant_total').val(total);

        }




        //------------------------ Valider la creation  du paiement

        function validerAchat() {



            let allValid = true;
            let fournisseur_id = parseInt($("#fournisseur_id").val(), 10);

            let nom_acheteur = $('#nom_acheteur').val().trim();

            let montant_total = parseFloat($('#montant_total').val().trim())     ;









             // Ajout des donnees de  produits
    let list_produit = [];


    let montant_produits = 0;
    $('#list_produit  tr').each(function () {


        var detail_produit = {

            produit_id: $(this).find("td:eq(0)").attr('data-id'),
            produit_name: $(this).find("td:eq(0)").text(),
            prix_unitaire: $(this).find("td:eq(1)").text(),
            quantite: $(this).find("td:eq(2)").text(),

            montant: $(this).find("td:eq(3)").text(),


        }



        list_produit.push(detail_produit);


    });



            if( montant_total == 0){
                $('.montant_total_error').text("Le  montant total ne peut etre   nulle ");
                allValid = false;

            }


            if (isNaN(fournisseur_id) || fournisseur_id === 0) {
                $('.fournisseur_id_error').text("Le choix du fournisseur      est obligatoire ");
                allValid = false;

            }


            if (nom_acheteur === '') {
                $('.nom_acheteur_error').text("Le nom de l ' acheteur '    est obligatoire ");
                allValid = false;

            }




            if (allValid) {

                $("#ajouterAchat").attr("disabled", true);



                let form = document.getElementById('form');
                let formData = new FormData(form);




                // Ajout des données de produit    a formData

        if (list_produit.length) {



            for (var i = 0; i < list_produit.length; i++) {
                formData.append('ligne_produits[' + i + '][produit_id]', list_produit[i].produit_id);
                formData.append('ligne_produits[' + i + '][produit_name]', list_produit[i].produit_name);
                formData.append('ligne_produits[' + i + '][montant]', list_produit[i].montant);
                formData.append('ligne_produits[' + i + '][quantite]', list_produit[i].quantite);
                formData.append('ligne_produits[' + i + '][prix_unitaire]', list_produit[i].prix_unitaire);



            }

        }


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/achats/save",
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
                                html: "Le code de paiement est " + data.achat_reference + "<br> Le montant total de l'achat " + data.montant + " Frs",
                                icon: 'info',
                                text: "",
                                type: "warning",
                                showCancelButton: !0,
                                confirmButtonText: "Liste des achats ",
                                cancelButtonText: "Nouvel acahat ",
                                reverseButtons: !0
                            }).then(function (e) {

                                if (e.value === true) {


                              location.href = '/achats/index';

                                } else {
                                    e.dismiss;

                                    location.href = '/achats/add';
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
