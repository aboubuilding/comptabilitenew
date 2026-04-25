
@extends('layout.app')

@section('title')

    Comptabilité | Lignes

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
                                        <button type="button" class="btn btn-primary" id="lancerLigne">
                                         + Ligne
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!--column-->
                            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                                <div class="table-responsive full-data">
                                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="example-student">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" class="form-check-input" id="checkAll" required="">
                                                </th>
                                                <th>Ligne </th>
                                                <th>Prix minimal    </th>
                                                <th>Prix maximal     </th>


                                                <th class="text-end" style="width: 10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $ligne )


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

                                                        <h4>{{ $ligne['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $ligne['prix_minimal'] }} </h6></td>

                                                <td><h6 class="mb-0">{{ $ligne['prix_plafond'] }} </h6></td>




                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierLigne" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$ligne['id']}}" title="Modifier " data-id="{{$ligne['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerLigne" data-id="{{$ligne['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>


                                        @endforeach


                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!--/column-->
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @include('admin.ligne.modal')

@endsection



@section('js')

    <!--datatables-->
    <script src="{{asset('admin')}}/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="{{asset('admin')}}/js/plugins-init/datatables.init.js"></script>

    <!-- Dashboard 1 -->
    <script src="{{asset('admin')}}/vendor/wow-master/dist/wow.min.js"></script>

    <script src="{{asset('admin/js/sweetalert2/sweetalert2.min.js')}}"></script>
    <script>
        jQuery(document).ready(function() {




            $("#lancerLigne").click(function(event) {
                event.preventDefault();

                lancerLigne()
            });

            $("#annulerLigne").click(function(event) {
                event.preventDefault();

                annulerLigne()
            });
            $(document).on('click', '#ajouterLigne', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerLigne()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierLigne', function() {

                let id = $(this).data('id');
                let url = "/lignes/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier une ligne  ');

                    let ligne_modal = $('#addLigne');

                    $(ligne_modal).find('form').find('input[name="libelle"]').val(data.ligne.libelle);
                    $(ligne_modal).find('form').find('input[name="prix_minimal"]').val(data.ligne.prix_minimal);
                    $(ligne_modal).find('form').find('input[name="prix_plafond"]').val(data.ligne.prix_plafond);


                    $('#idLigne').val(data.ligne.id);

                    $("#ajouterLigne").hide();
                    $("#updateLigne").show();

                    $(ligne_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerLigne', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateLigne").click(function(event) {
                event.preventDefault();

                updateLigne()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#prix_minimal').val('');
            $('#prix_plafond').val('');



            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterLigne").show();
            $("#updateLigne").hide();

        }

        //------------------------ Valider la catégorie

        function validerLigne() {

            let allValid = true;

            let libelle = $('#libelle').val().trim();
            let prix_minimal = parseInt($("#prix_minimal").val(), 10);
            let prix_plafond = parseInt($("#prix_plafond").val(), 10);





            if (libelle === '') {
                $('.libelle_error').text("Le libelle       est obligatoire ");
                allValid = false;

            }


            if (isNaN(prix_minimal) || prix_minimal === 0) {
                $('.prix_minimal_error').text("Le prix minimal    est obligatoire ");
                allValid = false;

            }


            if (isNaN(prix_plafond) || prix_plafond === 0) {
                $('.prix_plafond_error').text("Le prix plafond    est obligatoire ");
                allValid = false;

            }






            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/lignes/save",
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
        function lancerLigne() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  une ligne    ');

            $('#addLigne').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerLigne() {

            clearData();



            $('#addLigne').modal('toggle');
        }

        //------------------------ Update de Ligne
        function updateLigne() {



            let allValid = true;


            let libelle = $('#libelle').val().trim();
            let prix_minimal = parseInt($("#prix_minimal").val(), 10);
            let prix_plafond = parseInt($("#prix_plafond").val(), 10);





            if (libelle === '') {
                $('.libelle_error').text("Le libelle       est obligatoire ");
                allValid = false;

            }


            if (isNaN(prix_minimal) || prix_minimal === 0) {
                $('.prix_minimal_error').text("Le prix minimal    est obligatoire ");
                allValid = false;

            }


            if (isNaN(prix_plafond) || prix_plafond === 0) {
                $('.prix_plafond_error').text("Le prix plafond    est obligatoire ");
                allValid = false;

            }
            let id = $('#idLigne').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/lignes/update/" + id,
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



        //------------------------ fonction de suppression de Ligne

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer cette Ligne      ?",
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
                        url: "/lignes/delete/" + id,
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
