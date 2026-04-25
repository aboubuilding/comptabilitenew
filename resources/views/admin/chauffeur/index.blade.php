
@extends('layout.app')

@section('title')

    Comptabilité | Chauffeurs

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
                                        <button type="button" class="btn btn-primary" id="lancerChauffeur">
                                         + Chauffeur
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
                                                <th>Nom et prénom </th>
                                                <th>Télephone  </th>
                                                <th>Zone   </th>


                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $chauffeur )


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

                                                        <h4>{{ $chauffeur['nom_prenom'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $chauffeur['telephone'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $chauffeur['zone'] }} </h6></td>



                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierChauffeur" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$chauffeur['id']}}" title="Modifier " data-id="{{$chauffeur['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerChauffeur" data-id="{{$chauffeur['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>


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

@include('admin.chauffeur.modal')

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




            $("#lancerChauffeur").click(function(event) {
                event.preventDefault();

                lancerChauffeur()
            });

            $("#annulerChauffeur").click(function(event) {
                event.preventDefault();

                annulerChauffeur()
            });
            $(document).on('click', '#ajouterChauffeur', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerChauffeur()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierChauffeur', function() {

                let id = $(this).data('id');
                let url = "/chauffeurs/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un Chauffeur ');

                    let chauffeur_modal = $('#addChauffeur');

                    $(chauffeur_modal).find('form').find('input[name="nom"]').val(data.chauffeur.nom);
                    $(chauffeur_modal).find('form').find('input[name="prenom"]').val(data.chauffeur.prenom);
                    $(chauffeur_modal).find('form').find('input[name="telephone"]').val(data.chauffeur.telephone);




                    $('#idChauffeur').val(data.chauffeur.id);

                    $("#ajouterChauffeur").hide();
                    $("#updateChauffeur").show();

                    $(chauffeur_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerChauffeur', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateChauffeur").click(function(event) {
                event.preventDefault();

                updateChauffeur()
            });


            clearData();

        });



        function clearData() {

            $('#nom').val('');
            $('#prenom').val('');
            $('#telephone').val('');



            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

         

            $("#ajouterChauffeur").show();
            $("#updateChauffeur").hide();

        }

        //------------------------ Valider la catégorie

        function validerChauffeur() {

            let allValid = true;

            let nom = $('#nom').val().trim();
            let telephone = $('#telephone').val().trim();


            if (nom === '') {
                $('.nom_error').text("Le nom    est obligatoire ");
                allValid = false;

            }


            if (telephone === '') {
                $('.nom_error').text("Le telephone    est obligatoire ");
                allValid = false;

            }




            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/chauffeurs/save",
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
        function lancerChauffeur() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un chauffeur   ');

            $('#addChauffeur').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerChauffeur() {

            clearData();

            $('#addChauffeur').modal('toggle');
        }

        //------------------------ Update de Chauffeur
        function updateChauffeur() {



            let allValid = true;

            let nom = $('#nom').val().trim();
            let telephone = $('#telephone').val().trim();


            if (nom === '') {
                $('.nom_error').text("Le nom    est obligatoire ");
                allValid = false;

            }


            if (telephone === '') {
                $('.nom_error').text("Le telephone    est obligatoire ");
                allValid = false;

            }

            let id = $('#idChauffeur').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/chauffeurs/update/" + id,
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

                    Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.msg,
                            showConfirmButton: false,


                        },

                        setTimeout(function() {
                            location.reload();
                        }, 2000));



                },

                error: function(data) {

                    console.log(data);



                }



            });

            }


        }



        //------------------------ fonction de suppression de Chauffeur

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer ce  Chauffeur   ?",
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
                        url: "/chauffeurs/delete/" + id,
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
