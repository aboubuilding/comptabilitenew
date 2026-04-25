
@extends('layout.app')

@section('title')

    Comptabilité | Frais

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
                                        <button type="button" class="btn btn-primary" id="lancerFraisecole">
                                         + Frais
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
                                                <th>Montant </th>
                                                <th>Type paiement  </th>
                                                <th>Type forfait </th>

                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $fraisecole  )


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

                                                        <h4>{{ $fraisecole ['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $fraisecole ['montant'] }} </h6></td>

                                                <td>

                                                    @if($fraisecole ['type_paiement'] === \App\Types\TypePaiement::FRAIS_SCOLARITE)

                                                        <span class="badge badge-rounded badge-success">Scolarité </span>

                                                    @endif

                                                        @if($fraisecole ['type_paiement'] === \App\Types\TypePaiement::FRAIS_INSCRIPTION)

                                                            <span class="badge badge-rounded badge-success">Frais d inscription </span>

                                                        @endif


                                                        @if($fraisecole ['type_paiement'] === \App\Types\TypePaiement::FRAIS_ASSURANCE)

                                                            <span class="badge badge-rounded badge-success">Frais d' assurance  </span>

                                                        @endif

                                                        @if($fraisecole ['type_paiement'] === \App\Types\TypePaiement::LIVRE)

                                                            <span class="badge badge-rounded badge-success">Location de livre   </span>

                                                        @endif

                                                        @if($fraisecole ['type_paiement'] === \App\Types\TypePaiement::CANTINE)

                                                            <span class="badge badge-rounded badge-success">Cantine  </span>

                                                        @endif


                                                        @if($fraisecole ['type_paiement'] === \App\Types\TypePaiement::BUS)

                                                            <span class="badge badge-rounded badge-success">Bus   </span>

                                                        @endif


                                                        @if($fraisecole ['type_paiement'] === \App\Types\TypePaiement::FRAIS_EXTRA_SCOLAIRE)

                                                            <span class="badge badge-rounded badge-success">Activités extra scolaire    </span>

                                                        @endif




                                                </td>


                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierFraisecole" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$fraisecole ['id']}}" title="Modifier " data-id="{{$fraisecole ['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerFraisecole" data-id="{{$fraisecole ['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>
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

@include('admin.fraisecole.modal')

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




            $("#lancerFraisecole").click(function(event) {
                event.preventDefault();

                lancerFraisecole()
            });

            $("#annulerFraisecole").click(function(event) {
                event.preventDefault();

                annulerFraisecole()
            });
            $(document).on('click', '#ajouterFraisecole', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerFraisecole()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierFraisecole', function() {

                let id = $(this).data('id');
                let url = "/fraisecoles/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un frais  ');

                    let fraisecole_modal = $('#addFraisecole');

                    $(fraisecole_modal).find('form').find('input[name="libelle"]').val(data.fraisecole.libelle);

                    $(fraisecole_modal).find('form').find('input[name="montant"]').val(data.fraisecole.montant);
                    $(fraisecole_modal).find('form').find('select[name="type_paiement"]').val(data.fraisecole.type_paiement);
                    $(fraisecole_modal).find('form').find('select[name="type_forfait"]').val(data.fraisecole.type_forfait);
                    $(fraisecole_modal).find('form').find('select[name="niveau_id"]').val(data.fraisecole.niveau_id);




                    $('#idFraisecole').val(data.fraisecole.id);

                    $("#ajouterFraisecole").hide();
                    $("#updateFraisecole").show();

                    $(fraisecole_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerFraisecole', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateFraisecole").click(function(event) {
                event.preventDefault();

                updateFraisecole()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#montant').val('');
            $('#type_paiement').val('');
            $('#type_forfait').val('');
            $('#niveau_id').val('');






            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterFraisecole").show();
            $("#updateFraisecole").hide();

        }

        //------------------------ Valider la catégorie

        function validerFraisecole() {

            let allValid = true;
            let type_paiement = parseInt($("#type_paiement").val(), 10);
            let libelle = $('#libelle').val().trim();

            if (isNaN(type_paiement) || type_paiement === 0) {
                $('.type_paiement_error').text("Le type de paiement   est obligatoire ");
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
                    url: "/fraisecoles/save",
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
        function lancerFraisecole() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un frais    ');

            $('#addFraisecole').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerFraisecole() {

            clearData();


            $('#addFraisecole').modal('toggle');
        }

        //------------------------ Update de Fraisecole
        function updateFraisecole() {



            let allValid = true;

            let type_paiement = parseInt($("#type_paiement").val(), 10);
            let libelle = $('#libelle').val().trim();

            if (isNaN(type_paiement) || type_paiement === 0) {
                $('.type_paiement_error').text("Le type de paiement   est obligatoire ");
                allValid = false;

            }

            if (libelle === '') {
                $('.libelle_error').text("Le libelle   est obligatoire ");
                allValid = false;

            }

            let id = $('#idFraisecole').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/fraisecoles/update/" + id,
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



        //------------------------ fonction de suppression de Fraisecole

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer ce frais    ?",
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
                        url: "/fraisecoles/delete/" + id,
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
