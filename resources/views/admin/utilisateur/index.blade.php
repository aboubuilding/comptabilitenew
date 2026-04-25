
@extends('layout.app')

@section('title')

    Comptabilité | Utilisateurs

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
                                        <button type="button" class="btn btn-primary" id="lancerUtilisateur">
                                         + Utilisateur
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
                                                <th>Nom et prenom </th>
                                                <th>Login </th>
                                                <th>Email </th>
                                                <th>Role </th>

                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $utilisateur )


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

                                                        <h4>{{ $utilisateur['nom_prenom'] }}</h4>
                                                    </div>
                                                </td>


                                                <td><h6 class="mb-0">{{ $utilisateur['login'] }} </h6></td>
                                                <td><h6 class="mb-0">{{ $utilisateur['email'] }} </h6></td>

                                                <td>

                                                    @if($utilisateur['role'] === \App\Types\Role::ADMIN)

                                                        <span class="badge badge-rounded badge-success">Admin</span>

                                                    @endif

                                                        @if($utilisateur['role'] === \App\Types\Role::DIRECTEUR)

                                                            <span class="badge badge-rounded badge-success">Directeur </span>

                                                        @endif


                                                        @if($utilisateur['role'] === \App\Types\Role::COMPTABLE)

                                                            <span class="badge badge-rounded badge-success">Comptable </span>

                                                        @endif

                                                        @if($utilisateur['role'] === \App\Types\Role::CHEFCOMPTABLE)

                                                            <span class="badge badge-rounded badge-success">Chef comptable  </span>

                                                        @endif
                                                        @if($utilisateur['role'] === \App\Types\Role::COMPTABLE_DEPENSE)

                                                            <span class="badge badge-rounded badge-success">Comptable Dépense  </span>

                                                        @endif

                                                        @if($utilisateur['role'] === \App\Types\Role::ECONOME)

                                                            <span class="badge badge-rounded badge-success">Econome </span>

                                                        @endif


                                                        @if($utilisateur['role'] === \App\Types\Role::CAISSIER)

                                                            <span class="badge badge-rounded badge-success">Caissier </span>

                                                        @endif




                                                </td>


                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-primary shadow btn-xs sharp me-1 modifierUtilisateur" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$utilisateur['id']}}" title="Modifier " data-id="{{$utilisateur['id']}}"><i class="fa fa-pencil"></i></a>
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp supprimerUtilisateur" data-id="{{$utilisateur['id']}}"  title="Supprimer "><i class="fa fa-trash"></i></a>
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

@include('admin.utilisateur.modal')

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




            $("#lancerUtilisateur").click(function(event) {
                event.preventDefault();

                lancerUtilisateur()
            });

            $("#annulerUtilisateur").click(function(event) {
                event.preventDefault();

                annulerUtilisateur()
            });
            $(document).on('click', '#ajouterUtilisateur', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerUtilisateur()

            });




            //------------------------ Modification de la zone
            $(document).on('click', '.modifierUtilisateur', function() {

                let id = $(this).data('id');
                let url = "/utilisateurs/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Modifier un utilisateur      ');

                    let utilisateur_modal = $('#addUtilisateur');

                    $(utilisateur_modal).find('form').find('input[name="nom"]').val(data.utilisateur.nom);

                    $(utilisateur_modal).find('form').find('input[name="prenom"]').val(data.utilisateur.prenom);
                    $(utilisateur_modal).find('form').find('input[name="email"]').val(data.utilisateur.email);
                    $(utilisateur_modal).find('form').find('input[name="login"]').val(data.utilisateur.login);
                    $(utilisateur_modal).find('form').find('input[name="mot_passe"]').val(data.utilisateur.mot_passe);

                    $(utilisateur_modal).find('form').find('select[name="role"]').val(data.utilisateur.role);


                    $('#idUtilisateur').val(data.utilisateur.id);

                    $("#ajouterUtilisateur").hide();
                    $("#updateUtilisateur").show();

                    $(utilisateur_modal).modal('toggle');

                }, 'json')



            });


            $(document).on('click', '.supprimerUtilisateur', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });




            $("#updateUtilisateur").click(function(event) {
                event.preventDefault();

                updateUtilisateur()
            });


            clearData();

        });



        function clearData() {

            $('#nom').val('');
            $('#prenom').val('');
            $('#login').val('');
            $('#mot_passe').val('');
            $('#email').val('');






            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');

            $("#ajouterUtilisateur").show();
            $("#updateUtilisateur").hide();

        }

        //------------------------ Valider la catégorie

        function validerUtilisateur() {

            let allValid = true;
            let role = parseInt($("#role").val(), 10);
            let nom = $('#nom').val().trim();
            let prenom = $('#prenom').val().trim();
            let login = $('#login').val().trim();
            let mot_passe = $('#mot_passe').val().trim();
            if (isNaN(role) || role === 0) {
                $('.role_error').text("Le role   est obligatoire ");
                allValid = false;

            }

            if (mot_passe === '') {
                $('.mot_passe_error').text("Le mot de passe   est obligatoire ");
                allValid = false;

            }

            if (nom === '') {
                $('.nom_error').text("Le nom    est obligatoire ");
                allValid = false;

            }

            if (prenom === '') {
                $('.prenom_error').text("Le prénom     est obligatoire ");
                allValid = false;

            }

            if (login === '') {
                $('.login_error').text("Le login    est obligatoire ");
                allValid = false;

            }


            if (allValid) {



                let form = document.getElementById('form');
                let formData = new FormData(form);


                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/utilisateurs/save",
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
        function lancerUtilisateur() {

            clearData();

            $('#defaultModalLabel').text('Ajouter  un utilisateur   ');

            $('#addUtilisateur').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerUtilisateur() {

            clearData();



            $('#addUtilisateur').modal('toggle');
        }

        //------------------------ Update de Utilisateur
        function updateUtilisateur() {



            let allValid = true;

            let role = parseInt($("#role").val(), 10);
            let nom = $('#nom').val().trim();
            let prenom = $('#prenom').val().trim();
            let login = $('#login').val().trim();
            let mot_passe = $('#mot_passe').val().trim();
            if (isNaN(role) || role === 0) {
                $('.role_error').text("Le role   est obligatoire ");
                allValid = false;

            }

            if (mot_passe === '') {
                $('.mot_passe_error').text("Le mot de passe   est obligatoire ");
                allValid = false;

            }

            if (nom === '') {
                $('.nom_error').text("Le nom    est obligatoire ");
                allValid = false;

            }

            if (prenom === '') {
                $('.prenom_error').text("Le prénom     est obligatoire ");
                allValid = false;

            }

            if (login === '') {
                $('.login_error').text("Le login    est obligatoire ");
                allValid = false;

            }

            let id = $('#idUtilisateur').val();


            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);


            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/utilisateurs/update/" + id,
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



        //------------------------ fonction de suppression de Utilisateur

        function deleteConfirmation(id) {
            Swal.fire({
                title: "Voulez vous supprimer cet  utilisateur   ?",
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
                        url: "/utilisateurs/delete/" + id,
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
