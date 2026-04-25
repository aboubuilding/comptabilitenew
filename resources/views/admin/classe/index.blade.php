
@extends('layout.app')

@section('title')

    Comptabilité | Classes

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
                                        <button type="button" class="btn btn-primary" id="lancerClasse">
                                         + Classe
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
                                                <th>Niveau </th>
                                                <th>Classe </th>

                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $classe )


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

                                                        <h4>{{ $classe['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $classe['niveau'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $classe['cycle'] }} </h6></td>




                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierClasse" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$classe['id']}}" title="Modifier " data-id="{{$classe['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerClasse" data-id="{{$classe['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>
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

@include('admin.classe.modal')

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




            $("#lancerClasse").click(function(event) {
                event.preventDefault();

                lancerClasse()
            });

            $("#annulerClasse").click(function(event) {
                event.preventDefault();

                annulerClasse()
            });
            $(document).on('click', '#ajouterClasse', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerClasse()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierClasse', function() {

                let id = $(this).data('id');
                let url = "/classes/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier une classe       ');

                    let classe_modal = $('#addClasse');

                    $(classe_modal).find('form').find('input[name="libelle"]').val(data.classe.libelle);
                    $(classe_modal).find('form').find('textarea[name="emplacement"]').val(data.classe.emplacement);


                    $(classe_modal).find('form').find('select[name="niveau_id"]').val(data.classe.niveau_id);

                    $(classe_modal).find('form').find('select[name="cycle_id"]').val(data.classe.cycle_id);


                    $('#idClasse').val(data.classe.id);

                    $("#ajouterClasse").hide();
                    $("#updateClasse").show();

                    $(classe_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerClasse', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateClasse").click(function(event) {
                event.preventDefault();

                updateClasse()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#emplacement').val('');
            $('#cycle_id').val('');
            $('#niveau_id').val('');


            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterClasse").show();
            $("#updateClasse").hide();

        }

        //------------------------ Valider la catégorie

        function validerClasse() {

            let allValid = true;
            let cycle_id = parseInt($("#cycle_id").val(), 10);
            let niveau_id = parseInt($("#niveau_id").val(), 10);
            let libelle = $('#libelle').val().trim();

            if (isNaN(cycle_id) || cycle_id === 0) {
                $('.cycle_id_error').text("Le cycle    est obligatoire ");
                allValid = false;

            }

            if (isNaN(niveau_id) || niveau_id === 0) {
                $('.niveau_id_error').text("Le niveau     est obligatoire ");
                allValid = false;

            }


            if (libelle === '') {
                $('.libelle_error').text("Le libelle     est obligatoire ");
                allValid = false;

            }



            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/classes/save",
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
        function lancerClasse() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  une classe    ');

            $('#addClasse').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerClasse() {

            clearData();



            $('#addClasse').modal('toggle');
        }

        //------------------------ Update de Classe
        function updateClasse() {



            let allValid = true;

            let cycle_id = parseInt($("#cycle_id").val(), 10);
            let niveau_id = parseInt($("#niveau_id").val(), 10);
            let libelle = $('#libelle').val().trim();

            if (isNaN(cycle_id) || cycle_id === 0) {
                $('.cycle_id_error').text("Le cycle    est obligatoire ");
                allValid = false;

            }

            if (isNaN(niveau_id) || niveau_id === 0) {
                $('.niveau_id_error').text("Le niveau     est obligatoire ");
                allValid = false;

            }


            if (libelle === '') {
                $('.libelle_error').text("Le libelle     est obligatoire ");
                allValid = false;

            }

            let id = $('#idClasse').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/classes/update/" + id,
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



        //------------------------ fonction de suppression de Classe

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer cette classe    ?",
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
                        url: "/classes/delete/" + id,
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
