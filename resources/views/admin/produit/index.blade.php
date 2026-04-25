
@extends('layout.app')

@section('title')

    Comptabilité | Produits

@endsection

@section('css')



    <link href="{{asset('admin/css/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />


@endsection

@section('nav')
    @include('admin.aside')
@endsection



@section('contenu')


<div class="content-body">
            <!-- row -->
            <div class="container-fluid">
                <!-- Row -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="page-title flex-wrap">
                                    <div class="input-group search-area mb-md-0 mb-3">

                                    </div>
                                    <div>

                                        <!-- Button trigger modal -->
                                        <button type="button" class="btn btn-primary" id="lancerProduit">
                                         + Produit
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                                <div class="table-responsive full-data">
                                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="example-student">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" class="form-check-input" id="checkAll" required="">
                                                </th>
                                                <th>Libelle</th>
                                                <th>Prix unitaire </th>
                                                <th>Unité achat </th>
                                                <th>Unité stocks </th>
                                                <th>Type  </th>


                                                <th class="text-end" style="width: 10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $produit )


                                            <tr>
                                                <td>
                                                    <div class="checkbox me-0 align-self-center">
                                                        <div class="custom-control custom-checkbox ">
                                                            <input type="checkbox" class="form-check-input" id="check8" required="">
                                                            <label class="custom-control-label" for="check8"></label>
                                                        </div>
                                                    </div>
                                                </td>


                                                <td>
                                                    <div class="trans-list">

                                                        <h4>{{ $produit['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $produit['prix_unitaire'] }} </h6></td>

                                                <td><h6 class="mb-0">{{ $produit['unite_achat'] }} </h6></td>

                                                <td><h6 class="mb-0">{{ $produit['unite_stock'] }} </h6></td>
                                                <td>


                                                    @if($produit ['type_produit'] == 1)

                                                    <span class="badge badge-primary light badge-sm">Produit stockable </span>

                                                @endif


                                                @if($produit ['type_produit'] == 2)

                                                <span class="badge badge-primary light badge-sm">Service </span>

                                                @endif



                                                </td>




                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierProduit" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$produit['id']}}" title="Modifier " data-id="{{$produit['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerProduit" data-id="{{$produit['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>


                                        @endforeach


                                        </tbody>
                                    </table>
                                </div>
                            </div>



                        </div>
                    </div>
                </div>

            </div>
        </div>

@include('admin.produit.modal')

@endsection



@section('js')


    <script src="{{asset('admin')}}/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="{{asset('admin')}}/js/plugins-init/datatables.init.js"></script>


    <script src="{{asset('admin')}}/vendor/wow-master/dist/wow.min.js"></script>

    <script src="{{asset('admin/js/sweetalert2/sweetalert2.min.js')}}"></script>
    <script>
        jQuery(document).ready(function() {




            $("#lancerProduit").click(function(event) {
                event.preventDefault();

                lancerProduit()
            });

            $("#annulerProduit").click(function(event) {
                event.preventDefault();

                annulerProduit()
            });
            $(document).on('click', '#ajouterProduit', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerProduit()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierProduit', function() {

                let id = $(this).data('id');
                let url = "/produits/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un produit  ');

                    let produit_modal = $('#addProduit');

                    $(produit_modal).find('form').find('input[name="libelle"]').val(data.produit.libelle);
                    $(produit_modal).find('form').find('input[name="prix_unitaire"]').val(data.produit.prix_unitaire);
                    $(produit_modal).find('form').find('input[name="unite_stock"]').val(data.produit.unite_stock);
                    $(produit_modal).find('form').find('input[name="unite_achat"]').val(data.produit.unite_achat);
                    $(produit_modal).find('form').find('input[name="equivalence"]').val(data.produit.equivalence);
                    $(produit_modal).find('form').find('select[name="type_produit"]').val(data.produit.type_produit);





                    $('#idProduit').val(data.produit.id);

                    $("#ajouterProduit").hide();
                    $("#updateProduit").show();

                    $(produit_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerProduit', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateProduit").click(function(event) {
                event.preventDefault();

                updateProduit()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#prix_unitaire').val('');
            $('#unite_stock').val('');
            $('#unite_achat').val('');
            $('#equivalence').val('');
            $('#type_produit').val('');



            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterProduit").show();
            $("#updateProduit").hide();

        }

        //------------------------ Valider la catégorie

        function validerProduit() {

            let allValid = true;
            let prix_unitaire = parseInt($("#prix_unitaire").val(), 10);
            let type_produit = parseInt($("#type_produit").val(), 10);
            let unite_achat = $('#unite_achat').val().trim();
            let libelle = $('#libelle').val().trim();


            if (isNaN(prix_unitaire) || prix_unitaire === 0) {
                $('.prix_unitaire_error').text("Le prix unitaire     est obligatoire ");
                allValid = false;

            }


            if (isNaN(type_produit) || type_produit === 0) {
                $('.type_produit_error').text("Le type de produit      est obligatoire ");
                allValid = false;

            }



            if (libelle === '') {
                $('.libelle_error').text("Le libelle     est obligatoire ");
                allValid = false;

            }

            if (unite_achat === '') {
                $('.unite_achat_error').text("L '  unité ' achat    est obligatoire ");
                allValid = false;

            }




            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/produits/save",
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
                            $.each(data.error, function(prefix, val) {
                                $(form).find('span.' + prefix + '_error').text(val[0]);
                            });
                        } else {


                            Swal.fire({
                                    position: 'top-end',
                                    icon: 'success',
                                    title: data.msg,
                                    showConfirmButton: false,


                                },

                                setTimeout(function() {
                                    location.reload();
                                }, 2000));
                        }





                    },

                    error: function(data) {

                        console.log(data);



                    }



                });



            }
        }




        //------------------------ Ouvrir le popup d' ajout
        function lancerProduit() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un produit  ');

            $('#addProduit').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerProduit() {

            clearData();



            $('#addProduit').modal('toggle');
        }

        //------------------------ Update de Produit
        function updateProduit() {



            let allValid = true;

            let prix_unitaire = parseInt($("#prix_unitaire").val(), 10);
            let type_produit = parseInt($("#type_produit").val(), 10);
            let unite_achat = $('#unite_achat').val().trim();
            let libelle = $('#libelle').val().trim();


            if (isNaN(prix_unitaire) || prix_unitaire === 0) {
                $('.prix_error').text("Le prix unitaire     est obligatoire ");
                allValid = false;

            }


            if (isNaN(type_produit) || type_produit === 0) {
                $('.prix_error').text("Le type de produit      est obligatoire ");
                allValid = false;

            }



            if (libelle === '') {
                $('.libelle_error').text("Le libelle     est obligatoire ");
                allValid = false;

            }

            if (unite_achat === '') {
                $('.unite_achat_error').text("L '  unité ' achat    est obligatoire ");
                allValid = false;

            }

            let id = $('#idProduit').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/produits/update/" + id,
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


                    console.log(data.code)

                    if(data.code == 1 ){

                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.msg,
                            showConfirmButton: false,


                        },

                        setTimeout(function() {
                            location.reload();
                        }, 2000));

                    }




                },

                error: function(data) {

                    console.log(data);



                }



            });

            }


        }



        //------------------------ fonction de suppression de Produit

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer ce produit ?",
                icon: 'question',
                text: "",
                type: "warning",
                showCancelButton: !0,
                confirmButtonText: "Valider",
                cancelButtonText: "Annuler",
                reverseButtons: !0
            }).then(function(e) {

                if (e.value === true) {
                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

                    $.ajax({
                        type: 'POST',
                        url: "/produits/delete/" + id,
                        data: {
                            _token: CSRF_TOKEN
                        },
                        dataType: 'JSON',
                        success: function(results) {

                            console.log(results)
                            if (results.success === true) {
                                Swal.fire("Succès", results.message, "success");
                                // refresh page after 2 seconds
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                Swal.fire("Erreur!", results.message, "error");
                            }
                        }
                    });

                } else {
                    e.dismiss;
                }

            }, function(dismiss) {
                return false;
            })
        }
    </script>


@endsection
