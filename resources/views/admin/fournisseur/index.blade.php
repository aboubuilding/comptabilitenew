
@extends('layout.app')

@section('title')

    Comptabilité | Fournisseurs

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
                                        <button type="button" class="btn btn-primary" id="lancerFournisseur">
                                         + Fournisseur
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
                                                <th>Raison sociale </th>
                                                <th>Télephone  </th>
                                                <th>Chiffre d affaire </th>



                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $fournisseur )


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

                                                        <h4>{{ $fournisseur['raison_social'] }}</h4>
                                                    </div>
                                                </td>


                                                <td>
                                                    <div class="trans-list">

                                                        <h4>{{ $fournisseur['telephone_contact'] }}</h4>
                                                    </div>
                                                </td>


                                                <td>
                                                    <div class="trans-list">

                                                        <h4>{{ $fournisseur['chiffre_affaire'] }}</h4>
                                                    </div>
                                                </td>




                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierFournisseur" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$fournisseur['id']}}" title="Modifier " data-id="{{$fournisseur['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerFournisseur" data-id="{{$fournisseur['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>


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

@include('admin.fournisseur.modal')

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




            $("#lancerFournisseur").click(function(event) {
                event.preventDefault();

                lancerFournisseur()
            });

            $("#annulerFournisseur").click(function(event) {
                event.preventDefault();

                annulerFournisseur()
            });
            $(document).on('click', '#ajouterFournisseur', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerFournisseur()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierFournisseur', function() {

                let id = $(this).data('id');
                let url = "/fournisseurs/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un Fournisseur      ');

                    let fournisseur_modal = $('#addFournisseur');

                    $(fournisseur_modal).find('form').find('input[name="raison_social"]').val(data.fournisseur.raison_social);
                    $(fournisseur_modal).find('form').find('input[name="nom_contact"]').val(data.fournisseur.nom_contact);
                    $(fournisseur_modal).find('form').find('input[name="telephone_contact"]').val(data.fournisseur.telephone_contact);
                    $(fournisseur_modal).find('form').find('textarea[name="adresse"]').val(data.fournisseur.adresse);


                    $('#idFournisseur').val(data.fournisseur.id);

                    $("#ajouterFournisseur").hide();
                    $("#updateFournisseur").show();

                    $(fournisseur_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerFournisseur', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateFournisseur").click(function(event) {
                event.preventDefault();

                updateFournisseur()
            });


            clearData();

        });



        function clearData() {

            $('#raison_social').val('');
            $('#nom_contact').val('');
            $('#telephone_contact').val('');
            $('#adresse').val('');


            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterFournisseur").show();
            $("#updateFournisseur").hide();

        }

        //------------------------ Valider la catégorie

        function validerFournisseur() {

            let allValid = true;


            let telephone_contact = $('#telephone_contact').val().trim();
            let raison_social = $('#raison_social').val().trim();

            if (telephone_contact === '') {
                $('.telephone_contact_error').text("Le telephone     est obligatoire ");
                allValid = false;

            }


            if (raison_social === '') {
                $('.raison_social_error').text("La raison sociale     est obligatoire ");
                allValid = false;

            }




            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/fournisseurs/save",
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
        function lancerFournisseur() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un fournisseur   ');

            $('#addFournisseur').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerFournisseur() {

            clearData();



            $('#addFournisseur').modal('toggle');
        }

        //------------------------ Update de Fournisseur
        function updateFournisseur() {



            let allValid = true;

            let telephone_contact = $('#telephone_contact').val().trim();
            let raison_social = $('#raison_social').val().trim();

            if (telephone_contact === '') {
                $('.telephone_contact_error').text("Le telephone     est obligatoire ");
                allValid = false;

            }


            if (raison_social === '') {
                $('.raison_social_error').text("La raison sociale     est obligatoire ");
                allValid = false;

            }



            let id = $('#idFournisseur').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/fournisseurs/update/" + id,
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



        //------------------------ fonction de suppression de Fournisseur

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer ce fournisseur    ?",
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
                        url: "/fournisseurs/delete/" + id,
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
