
@extends('layout.app')

@section('title')

    Comptabilité | Mes caisses

@endsection

@section('titre')
    Liste de mes caisses
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
                                        <button type="button" class="btn btn-primary" id="lancerCaisse">
                                         + Caisse
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
                                                <th>Solde initial  </th>
                                                <th>Solde final  </th>
                                                <th>Total encaissement </th>
                                                <th>Statut   </th>


                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $caisse )


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

                                                        <h4>{{ $caisse['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $caisse['solde_initial'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $caisse['solde_final'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $caisse['total_encaissement'] }} </h6></td>

                                                <td>

                                                    @if($caisse ['statut'] === \App\Types\StatutCaisse::OUVERT)

                                                        <span class="badge badge-success light badge-sm">Ouvert<span
                                                                class="ms-1 fa fa-redo"></span></span>



                                                    @endif

                                                    @if($caisse ['statut'] === \App\Types\StatutCaisse::CLOTURE)

                                                        <span class="badge badge-primary light badge-sm">Cloturée  <span
                                                                class="ms-1 fa fa-check"></span></span>

                                                    @endif





                                                </td>





                                                <td>
                                                    <div class="d-flex">


                                                        <a href="{{url('/caisses/detail/'.$caisse['id'])}}" class="btn btn-success shadow btn-xs sharp me-1 detailCaisse" style="background-color: #1EA1F3; border: red" data-id="{{$caisse['id']}}" title="Details des mouvements  " data-id="{{$caisse['id']}}"><i class="fa fa-plus"></i></a>



                                                    @if($caisse['total_encaissement'] == 0)
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp me-1 supprimerCaisse" style="background-color: red; border: #1EA1F3" data-id="{{$caisse['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>

                                                        @endif


                                                        @if($caisse['statut'] == \App\Types\StatutCaisse::OUVERT)

                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierCaisse" style="background-color: #0e5a2f; border: #1EA1F3" data-id="{{$caisse['id']}}" title="Modifier " data-id="{{$caisse['id']}}"><i class="fa fa-pencil"></i></a>

                                                          @endif



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

@include('admin.caisse.modal')

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


            $("#lancerCaisse").click(function(event) {
                event.preventDefault();

                lancerCaisse()
            });

            $("#annulerCaisse").click(function(event) {
                event.preventDefault();

                annulerCaisse()
            });
            $(document).on('click', '#ajouterCaisse', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerCaisse()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierCaisse', function() {

                let id = $(this).data('id');
                let url = "/caisses/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier une caisse  ');

                    let caisse_modal = $('#addCaisse');

                    $(caisse_modal).find('form').find('input[name="libelle"]').val(data.caisse.libelle);
                    $(caisse_modal).find('form').find('input[name="solde_initial"]').val(data.caisse.solde_initial);


                    $('#idCaisse').val(data.caisse.id);



                    $("#addCaisse #ajouterCaisse").hide();
                    $("#addCaisse #updateCaisse").show();


                    $(caisse_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerCaisse', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateCaisse").click(function(event) {
                event.preventDefault();

                updateCaisse()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#solde_initial').val('');



            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');



            $("#addCaisse #ajouterCaisse").show();
            $("#addCaisse #updateCaisse").hide();


        }

        //------------------------ Valider la catégorie

        function validerCaisse() {

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
                    url: "/caisses/save",
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
        function lancerCaisse() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  une caisse   ');

            $('#addCaisse').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerCaisse() {

            clearData();



            $('#addCaisse').modal('toggle');
        }

        //------------------------ Update de Caisse
        function updateCaisse() {



            let allValid = true;



            let libelle = $('#libelle').val().trim();



            if (libelle === '') {
                $('.libelle_error').text("Le libelle     est obligatoire ");
                allValid = false;

            }

            let id = $('#idCaisse').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/caisses/update/" + id,
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



        //------------------------ fonction de suppression de Caisse

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer cette caisse    ?",
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
                        url: "/caisses/delete/" + id,
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
