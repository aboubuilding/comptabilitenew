
@extends('layout.app')

@section('title')

    Comptabilité | Niveaux

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
                                        <button type="button" class="btn btn-primary" id="lancerNiveau">
                                         + Niveau
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
                                                <th>Cycle </th>


                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $niveau )


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

                                                        <h4>{{ $niveau['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $niveau['cycle'] }} </h6></td>



                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierNiveau" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$niveau['id']}}" title="Modifier " data-id="{{$niveau['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerNiveau" data-id="{{$niveau['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>


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

@include('admin.niveau.modal')

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




            $("#lancerNiveau").click(function(event) {
                event.preventDefault();

                lancerNiveau()
            });

            $("#annulerNiveau").click(function(event) {
                event.preventDefault();

                annulerNiveau()
            });
            $(document).on('click', '#ajouterNiveau', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerNiveau()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierNiveau', function() {

                let id = $(this).data('id');
                let url = "/niveaux/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un niveau       ');

                    let niveau_modal = $('#addNiveau');

                    $(niveau_modal).find('form').find('input[name="libelle"]').val(data.niveau.libelle);
                    $(niveau_modal).find('form').find('textarea[name="description"]').val(data.niveau.description);
                    $(niveau_modal).find('form').find('input[name="numero_ordre"]').val(data.niveau.numero_ordre);


                    $(niveau_modal).find('form').find('select[name="cycle_id"]').val(data.niveau.cycle_id);


                    $('#idNiveau').val(data.niveau.id);

                    $("#ajouterNiveau").hide();
                    $("#updateNiveau").show();

                    $(niveau_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerNiveau', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateNiveau").click(function(event) {
                event.preventDefault();

                updateNiveau()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#description').val('');
            $('#numero_ordre').val('');
            $('#cycle_id').val('');


            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterNiveau").show();
            $("#updateNiveau").hide();

        }

        //------------------------ Valider la catégorie

        function validerNiveau() {

            let allValid = true;
            let cycle_id = parseInt($("#cycle_id").val(), 10);
            let libelle = $('#libelle').val().trim();


            if (isNaN(cycle_id) || cycle_id === 0) {
                $('.cycle_id_error').text("Le cycle    est obligatoire ");
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
                    url: "/niveaux/save",
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
        function lancerNiveau() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un niveau   ');

            $('#addNiveau').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerNiveau() {

            clearData();

            $('#addNiveau').modal('toggle');
        }

        //------------------------ Update de Niveau
        function updateNiveau() {



            let allValid = true;

            let cycle_id = parseInt($("#cycle_id").val(), 10);
            let libelle = $('#libelle').val().trim();


            if (isNaN(cycle_id) || cycle_id === 0) {
                $('.cycle_id_error').text("Le cycle    est obligatoire ");
                allValid = false;

            }


            if (libelle === '') {
                $('.libelle_error').text("Le libelle   est obligatoire ");
                allValid = false;

            }

            let id = $('#idNiveau').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/niveaux/update/" + id,
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



        //------------------------ fonction de suppression de Niveau

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer ce  niveau   ?",
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
                        url: "/niveaux/delete/" + id,
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
