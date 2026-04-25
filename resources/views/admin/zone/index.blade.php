
@extends('layout.app')

@section('title')

    Comptabilité | Zones

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
                                        <button type="button" class="btn btn-primary" id="lancerZone">
                                         + Zone
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
                                                <th>Libelle </th>
                                                <th> Voiture   </th>
                                                <th>Chauffeur    </th>
                                                <th>Total élèves    </th>
                                                <th>Dépenses     </th>
                                                <th>Chiffre    </th>


                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $zone )


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

                                                        <h4>{{ $zone['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $zone['voiture'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $zone['chauffeur'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $zone['total_eleve'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $zone['total_depense'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $zone['chiffre_affaire'] }} </h6></td>



                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierZone" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$zone['id']}}" title="Modifier " data-id="{{$zone['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerZone" data-id="{{$zone['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>


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

@include('admin.zone.modal')

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




            $("#lancerZone").click(function(event) {
                event.preventDefault();

                lancerZone()
            });

            $("#annulerZone").click(function(event) {
                event.preventDefault();

                annulerZone()
            });
            $(document).on('click', '#ajouterZone', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerZone()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierZone', function() {

                let id = $(this).data('id');
                let url = "/zones/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier une zone ');

                    let zone_modal = $('#addZone');

                    $(zone_modal).find('form').find('input[name="libelle"]').val(data.zone.libelle);
                    $(zone_modal).find('form').find('textarea[name="description"]').val(data.zone.description);
                    $(zone_modal).find('form').find('select[name="chauffeur_id"]').val(data.zone.chauffeur_id);
                    $(zone_modal).find('form').find('select[name="voiture_id"]').val(data.zone.voiture_id);

                    $('#idZone').val(data.zone.id);

                    $("#ajouterZone").hide();
                    $("#updateZone").show();

                    $(zone_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerZone', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateZone").click(function(event) {
                event.preventDefault();

                updateZone()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#description').val('');
            $('#chauffeur_id').val('');
            $('#voiture_id').val('');



            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');



            $("#ajouterZone").show();
            $("#updateZone").hide();

        }

        //------------------------ Valider la catégorie

        function validerZone() {

            let allValid = true;

            let libelle = $('#libelle').val().trim();
            let chauffeur_id = parseInt($("#cycle_id").val(), 10);
            let voiture_id = parseInt($("#voiture_id").val(), 10);



            if (isNaN(chauffeur_id) || chauffeur_id === 0) {
                $('.chauffeur_id_error').text("Le chauffeur     est obligatoire ");
                allValid = false;

            }

            if (isNaN(voiture_id) || voiture_id === 0) {
                $('.voiture_id_error').text("La voiture   est obligatoire ");
                allValid = false;

            }


            if (libelle === '') {
                $('.libelle_error').text("Le libelle   est obligatoire ");
                allValid = false;

            }





            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/zones/save",
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
        function lancerZone() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  une zone   ');

            $('#addZone').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerZone() {

            clearData();

            $('#addZone').modal('toggle');
        }

        //------------------------ Update de Zone
        function updateZone() {



            let allValid = true;

            let libelle = $('#libelle').val().trim();
            let chauffeur_id = parseInt($("#cycle_id").val(), 10);
            let voiture_id = parseInt($("#voiture_id").val(), 10);



            if (isNaN(chauffeur_id) || chauffeur_id === 0) {
                $('.chauffeur_id_error').text("Le chauffeur     est obligatoire ");
                allValid = false;

            }

            if (isNaN(voiture_id) || voiture_id === 0) {
                $('.voiture_id_error').text("La voiture   est obligatoire ");
                allValid = false;

            }


            if (libelle === '') {
                $('.libelle_error').text("Le libelle   est obligatoire ");
                allValid = false;

            }

            let id = $('#idZone').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/zones/update/" + id,
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



        //------------------------ fonction de suppression de Zone

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer cette zone    ?",
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
                        url: "/zones/delete/" + id,
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
