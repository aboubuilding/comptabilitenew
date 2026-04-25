
@extends('layout.app')

@section('title')

    Comptabilité | Articles

@endsection

@section('css')



    <link href="{{asset('admin/css/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />


@endsection

@section('nav')
    @include('admin.aside')
@endsection



@section('contenu')

@php



            $user_value = session()->get('LoginUser');
            $compte_id = $user_value['compte_id'];

            $utilisateur = App\Models\User::rechercheUserById($compte_id);

            $role  = $utilisateur->role;
@endphp


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
                                        <button type="button" class="btn btn-primary" id="lancerArticle">
                                         + Article
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
                                                <th>Montant  </th>
                                                <th>Total  </th>

                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $article )


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

                                                        <h4>{{ $article['libelle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $article['montant'] }} </h6></td>


                                                @if( $role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN )

                                                <td><h6 class="mb-0">{{ $article['total_article'] }} </h6></td>


                                                @endif



                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierArticle" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$article['id']}}" title="Modifier " data-id="{{$article['id']}}"><i class="fa fa-pencil"></i></a>

                                                        @if( $article['total_article'] == 0)
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerArticle" data-id="{{$article['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>

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

@include('admin.article.modal')

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




            $("#lancerArticle").click(function(event) {
                event.preventDefault();

                lancerArticle()
            });

            $("#annulerArticle").click(function(event) {
                event.preventDefault();

                annulerArticle()
            });
            $(document).on('click', '#ajouterArticle', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerArticle()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierArticle', function() {

                let id = $(this).data('id');
                let url = "/articles/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un Article        ');

                    let article_modal = $('#addArticle');

                    $(article_modal).find('form').find('input[name="libelle"]').val(data.article.libelle);
                    $(article_modal).find('form').find('input[name="montant"]').val(data.article.montant);





                    $('#idArticle').val(data.article.id);

                    $("#ajouterArticle").hide();
                    $("#updateArticle").show();

                    $(article_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerArticle', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateArticle").click(function(event) {
                event.preventDefault();

                updateArticle()
            });


            clearData();

        });



        function clearData() {

            $('#libelle').val('');
            $('#montant').val('');



            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterArticle").show();
            $("#updateArticle").hide();

        }

        //------------------------ Valider la catégorie

        function validerArticle() {

            let allValid = true;
            let montant = parseInt($("#montant").val(), 10);
            let libelle = $('#libelle').val().trim();

            if (isNaN(montant) || montant === 0) {
                $('.montant_error').text("Le montant     est obligatoire ");
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
                    url: "/articles/save",
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
        function lancerArticle() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un article   ');

            $('#addArticle').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerArticle() {

            clearData();



            $('#addArticle').modal('toggle');
        }

        //------------------------ Update de Article
        function updateArticle() {



            let allValid = true;

            let montant  = parseInt($("#montant").val(), 10);
            let libelle = $('#libelle').val().trim();

            if (isNaN(montant) || montant === 0) {
                $('.montant_error').text("Le montant      est obligatoire ");
                allValid = false;

            }



            if (libelle === '') {
                $('.libelle_error').text("Le libelle     est obligatoire ");
                allValid = false;

            }
            let id = $('#idArticle').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/articles/update/" + id,
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



        //------------------------ fonction de suppression de Article

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer cet article      ?",
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
                        url: "/articles/delete/" + id,
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
