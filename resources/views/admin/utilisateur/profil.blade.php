@extends('layout.app')

@section('title')
    Comptabilité | Mon profil
@endsection

@section('titre')
    Mon profil {{ $utilisateur->nom . ' ' . $utilisateur->prenom }}
@endsection



@section('css')
    <link href="{{ asset('admin/css/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('nav')
    @include('admin.aside')
@endsection



@section('contenu')
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">






            <div class="row">
                <div class="col-xl-12">
                    <div class="card">


                        <div class="custom-tab-1">

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="encaissement" role="tabpanel">
                                    <div class="row" style="margin: 20px">
                                        <div class="col-xl-12">


                                            <div class="row">
                                                <div class="col-xl-7 wow fadeInUp" data-wow-delay="1.5s">
                                                    <legend>Editer profil</legend>
                                                    <hr>
                                                    <div class="card">
                                                        <div class="card-body pb-xl-4 pb-sm-3 pb-0">

                                                            <form method="post" action="#"
                                                                enctype="multipart/form-data" id="form2">

                                                                @csrf
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nom</label>
                                                                    <input type="text" class="form-control"
                                                                        name="nom" id="nom"
                                                                        value="{{ $utilisateur->nom }}" disabled>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Prénom</label>
                                                                    <input type="text" class="form-control"
                                                                        name="prenom" id="prenom"
                                                                        value="{{ $utilisateur->prenom }}" disabled>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Login</label>
                                                                    <input type="text" class="form-control"
                                                                        name="login" id="login"
                                                                        value="{{ $utilisateur->login }}" disabled>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Email</label>
                                                                    <input type="email" class="form-control"
                                                                        name="email" id="email"
                                                                        value="{{ $utilisateur->email }}" disabled>
                                                                </div>
                                                                <button class="btn btn-primary" disabled><i
                                                                        class="fa fa-save"></i> Enregistrer</button>

                                                            </form>


                                                        </div>
                                                        <!--/column-->
                                                    </div>
                                                </div>
                                                <div class="col-xl-5 " data-wow-delay="1.5s">
                                                    <legend>Changer de mot de passe</legend>
                                                    <hr>
                                                    <div class="card">
                                                        <div class="card-body pb-xl-4 pb-sm-3 pb-0">

                                                            <form method="post" enctype="multipart/form-data"
                                                                id="form">

                                                                @csrf
                                                                <div class="mb-3">
                                                                    <label class="form-label">Ancien mot de passe</label>
                                                                    <input type="password" class="form-control"
                                                                        name="old_pwd" id="old_pwd" required>
                                                                    <span class="text-danger error-text old_pwd_error">
                                                                    </span>
                                                                </div>


                                                                <div class="mb-3">
                                                                    <label class="form-label">Nouveau mot de passe</label>
                                                                    <input type="password" class="form-control"
                                                                        name="new_pwd" id="new_pwd" required>
                                                                    <span class="text-danger error-text new_pwd_error">
                                                                    </span>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Confirmer le nouveau mot de
                                                                        passe</label>
                                                                    <input type="password" class="form-control"
                                                                        name="conf_pwd" id="conf_pwd" required>
                                                                    <span class="text-danger error-text conf_pwd_error"></span>
                                                                </div>


                                                                <button id="changePassword" class="btn btn-primary"><i
                                                                        class="fa fa-save"></i> Enregistrer</button>

                                                            </form>


                                                        </div>
                                                        <!--/column-->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>



                </div>
            </div>
        @endsection



        @section('js')
            <!--datatables-->
            <script src="{{ asset('admin') }}/vendor/datatables/js/jquery.dataTables.min.js"></script>
            <script src="{{ asset('admin') }}/js/plugins-init/datatables.init.js"></script>

            <!-- Dashboard 1 -->
            <script src="{{ asset('admin') }}/vendor/wow-master/dist/wow.min.js"></script>

            <script src="{{ asset('admin/js/sweetalert2/sweetalert2.min.js') }}"></script>


            <script>
                jQuery(document).ready(function() {



                    clearData();

                    //--------------------------------- changement de choix de l eleve



                    $("#changePassword").click(function(event) {
                        event.preventDefault();

                        let form = document.getElementById('form');
                        let formData = new FormData(form);
                        $.ajax({
                            dataType: 'json',
                            type: 'POST',
                            url: "{{ route('admin_utilisateur_change_password') }}",
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
                                    if(data.error==null){
                                        Swal.fire({
                                            position: 'top-end',
                                            icon: 'error',
                                            title: data.msg,
                                            showConfirmButton: false,
                                        },
                                        setTimeout(function() {
                                        
                                        }, 2000));
                                    }
                                } else {
                                    Swal.fire({
                                            position: 'top-end',
                                            icon: 'success',
                                            title: data.msg,
                                            showConfirmButton: false,

                                        },
                                        setTimeout(function() {
                                            clearData();
                                        }, 2000));
                                }
                            },
                            error: function(data) {

                                console.log(data);
                            }
                        });
                    });






                });

                function clearData() {

                    $('#old_pwd').val('');
                    $('#new_pwd').val('');
                    $('#conf_pwd').val('');

                    let form = document.getElementById('form');
                    $(form).find('span.error-text').text('');

                }
            </script>
        @endsection
