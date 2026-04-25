
@extends('layout.app')

@section('title')

    Comptabilité | Cycles

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
                                        <button type="button" class="btn btn-primary" id="lancerCycle">
                                         + Cycle
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
                                                <th>Libelle</th>


                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $cycle )


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

                                                        <h4>{{ $cycle['libelle'] }}</h4>
                                                    </div>
                                                </td>




                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierCycle" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$cycle['id']}}" title="Modifier " data-id="{{$cycle['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerCycle" data-id="{{$cycle['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>


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

@include('admin.cycle.modal')

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




            $("#lancerCycle").click(function(event) {
                event.preventDefault();

                lancerCycle()
            });

            $("#annulerCycle").click(function(event) {
                event.preventDefault();

                annulerCycle()
            });
            $(document).on('click', '#ajouterCycle', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerCycle()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierCycle', function() {

                let id = $(this).data('id');
                let url = "/cycles/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un Cycle      ');

                    let cycle_modal = $('#addCycle');

                    $(cycle_modal).find('form').find('input[name="libelle"]').val(data.cycle.libelle);


                    $('#idCycle').val(data.cycle.id);

                    $("#ajouterCycle").hide();
                    $("#updateCycle").show();

                    $(cycle_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerCycle', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateCycle").click(function(event) {
                event.preventDefault();

                updateCycle()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');


            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterCycle").show();
            $("#updateCycle").hide();

        }

        //------------------------ Valider la catégorie

        function validerCycle() {

            let allValid = true;
            let libelle = parseInt($("#libelle").val(), 10);


            if (libelle === '') {
                $('.libelle_error').text("Le libelle    est obligatoire ");
                allValid = false;

            }




            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/cycles/save",
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
        function lancerCycle() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un cycle   ');

            $('#addCycle').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerCycle() {

            clearData();



            $('#addCycle').modal('toggle');
        }

        //------------------------ Update de Cycle
        function updateCycle() {



            let allValid = true;

            let libelle = parseInt($("#libelle").val(), 10);


            if (libelle === '') {
                $('.nom_error').text("Le libelle    est obligatoire ");
                allValid = false;

            }



            let id = $('#idCycle').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/cycles/update/" + id,
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



        //------------------------ fonction de suppression de Cycle

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer ce cycle    ?",
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
                        url: "/cycles/delete/" + id,
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
