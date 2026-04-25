
@extends('layout.app')

@section('title')

    Comptabilité | Voitures

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
                                        <button type="button" class="btn btn-primary" id="lancerVoiture">
                                         + Voiture
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
                                                <th>Plaque </th>
                                                <th>Marque   </th>
                                                <th>Nombre de place    </th>


                                                <th class="text-end" style="width: 10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $voiture )


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

                                                        <h4>{{ $voiture['plaque'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $voiture['marque'] }} </h6></td>

                                                <td><h6 class="mb-0">{{ $voiture['nombre_place'] }} </h6></td>




                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierVoiture" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$voiture['id']}}" title="Modifier " data-id="{{$voiture['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerVoiture" data-id="{{$voiture['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>
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

        @include('admin.voiture.modal')

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




            $("#lancerVoiture").click(function(event) {
                event.preventDefault();

                lancerVoiture()
            });

            $("#annulerVoiture").click(function(event) {
                event.preventDefault();

                annulerVoiture()
            });
            $(document).on('click', '#ajouterVoiture', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerVoiture()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierVoiture', function() {

                let id = $(this).data('id');
                let url = "/voitures/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier une voiture ');

                    let voiture_modal = $('#addVoiture');

                    $(voiture_modal).find('form').find('input[name="plaque"]').val(data.voiture.plaque);
                    $(voiture_modal).find('form').find('input[name="marque"]').val(data.voiture.marque);
                    $(voiture_modal).find('form').find('input[name="nombre_place"]').val(data.voiture.nombre_place);


                    $('#idVoiture').val(data.voiture.id);

                    $("#ajouterVoiture").hide();
                    $("#updateVoiture").show();

                    $(voiture_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerVoiture', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateVoiture").click(function(event) {
                event.preventDefault();

                updateVoiture()
            });


            clearData();

        });



        function clearData() {

            $('#plaque').val('');
            $('#marque').val('');
            $('#nombre_place').val('');



            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterVoiture").show();
            $("#updateVoiture").hide();

        }

        //------------------------ Valider la catégorie

        function validerVoiture() {

            let allValid = true;

            let plaque = $('#plaque').val().trim();




            if (plaque === '') {
                $('.plaque_error').text("La plaque      est obligatoire ");
                allValid = false;

            }



            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/voitures/save",
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
        function lancerVoiture() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  une voiture   ');

            $('#addVoiture').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerVoiture() {

            clearData();



            $('#addVoiture').modal('toggle');
        }

        //------------------------ Update de Voiture
        function updateVoiture() {



            let allValid = true;


            let plaque = $('#plaque').val().trim();




            if (plaque === '') {
                $('.plaque_error').text("La plaque      est obligatoire ");
                allValid = false;

            }
            let id = $('#idVoiture').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/voitures/update/" + id,
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



        //------------------------ fonction de suppression de Voiture

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer cette voiture      ?",
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
                        url: "/voitures/delete/" + id,
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
