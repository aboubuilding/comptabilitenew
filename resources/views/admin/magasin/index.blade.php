
@extends('layout.app')

@section('title')

    Comptabilité | Magasins

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
                                        <button type="button" class="btn btn-primary" id="lancerMagasin">
                                         + Magasin
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
                                                <th>Responsable  </th>


                                                <th class="text-end" style="width: 10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $magasin )


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

                                                        <h4>{{ $magasin['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $magasin['responsable'] }} </h6></td>




                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierMagasin" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$magasin['id']}}" title="Modifier " data-id="{{$magasin['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerMagasin" data-id="{{$magasin['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>
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

        @include('admin.magasin.modal')

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




            $("#lancerMagasin").click(function(event) {
                event.preventDefault();

                lancerMagasin()
            });

            $("#annulerMagasin").click(function(event) {
                event.preventDefault();

                annulerMagasin()
            });
            $(document).on('click', '#ajouterMagasin', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerMagasin()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierMagasin', function() {

                let id = $(this).data('id');
                let url = "/magasins/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un Magasin        ');

                    let magasin_modal = $('#addMagasin');

                    $(magasin_modal).find('form').find('input[name="libelle"]').val(data.magasin.libelle);
                    $(magasin_modal).find('form').find('input[name="responsable"]').val(data.magasin.responsable);
                    $(magasin_modal).find('form').find('textarea[name="description"]').val(data.magasin.description);





                    $('#idMagasin').val(data.magasin.id);

                    $("#ajouterMagasin").hide();
                    $("#updateMagasin").show();

                    $(magasin_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerMagasin', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateMagasin").click(function(event) {
                event.preventDefault();

                updateMagasin()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#responsable').val('');
            $('#description').val('');



            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterMagasin").show();
            $("#updateMagasin").hide();

        }

        //------------------------ Valider la catégorie

        function validerMagasin() {

            let allValid = true;

            let libelle = $('#libelle').val().trim();




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
                    url: "/magasins/save",
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
        function lancerMagasin() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un magasin  ');

            $('#addMagasin').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerMagasin() {

            clearData();



            $('#addMagasin').modal('toggle');
        }

        //------------------------ Update de Magasin
        function updateMagasin() {



            let allValid = true;


            let libelle = $('#libelle').val().trim();





            if (libelle === '') {
                $('.libelle_error').text("Le libelle     est obligatoire ");
                allValid = false;

            }
            let id = $('#idMagasin').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/magasins/update/" + id,
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



        //------------------------ fonction de suppression de Magasin

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer ce Magasin     ?",
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
                        url: "/magasins/delete/" + id,
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
