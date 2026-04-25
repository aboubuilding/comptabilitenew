<!DOCTYPE html>
<html lang="en" class="h-100">


<!-- Mirrored from akademi.dexignlab.com/flask/demo/page-login.html by HTTrack Website Copier/3.x [XR&CO'2014], Fri, 14 Jun 2024 09:36:21 GMT -->
<head>
    <!-- All Meta -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

     <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Mobile Specific -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Page Title Here -->
      <title>Ecole Mariam | Comptabilite</title>

    <!-- FAVICONS ICON -->
    <link rel="shortcut icon" type="image/png" href="{{ asset('admin') }}/images/favicon.png" >
    <link href="{{ asset('admin') }}/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0">
    <link href="{{ asset('admin') }}/css/style.css" rel="stylesheet">

</head>

<body class="body  h-100">
<div class="authincation d-flex flex-column flex-lg-row flex-column-fluid">
    <div class="login-aside text-center  d-flex flex-column flex-row-auto">


    </div>
    <div class="container flex-row-fluid d-flex flex-column justify-content-center position-relative overflow-hidden p-7 mx-auto">
        <div class="d-flex justify-content-center h-100 align-items-center">
            <div class="authincation-content style-2">
                <div class="row no-gutters">
                    <div class="col-xl-12 tab-content">
                        <div id="sign-up" class="auth-form tab-pane fade show active  form-validation">
                            <form action="">
                                <div class="text-center mb-4">

                                    <div class="text-center mb-3">
                                        <a href="{{ url('/') }}"><img src="{{ asset('admin') }}/images/logo_mariam.png"
                                                                      alt="" style="width: 100px; margin: auto"></a>
                                    </div>
                                    <h3 class="text-center mb-2 text-black">Comptabilité | Connexion  </h3>

                                </div>

                                <span class="text-danger" id="erreurserveur"></span>

                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label mb-2 fs-13 label-color font-w500">Login  </label>
                                    <input type="text" class="form-control" id="login"  name="login">

                                    <span class="text-danger" id="erreurlogin"></span>
                                </div>
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label mb-2 fs-13 label-color font-w500">Mot de passe </label>
                                    <input type="password" class="form-control" id="mot_passe" name="mot_passe">

                                  <span class="text-danger" id="erreurmotpasse"></span>
                                </div>
                                <a href="#" class="text-primary float-end mb-4">Mot de passe oublié </a>
                                <button class="btn btn-block btn-primary" id="authentifier">Se connecter</button>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Required vendors -->
<script src="{{ asset('admin') }}/vendor/global/global.min.js"></script>
<script src="{{ asset('admin') }}/js/custom.min.js"></script>
<script src="{{ asset('admin') }}/js/dlabnav-init.js"></script>

   <script>
        jQuery(document).ready(function() {



            $("#authentifier").click(function(event) {
                event.preventDefault();


                authentifier()
            });



            clearData()

        });

        function clearData() {


            $('#login').val('');
            $('#mot_passe').val('');



            $('#erreurlogin').text('');
            $('#erreurmotpasse').text('');
            $('#erreurserveur').text('');




        }



        //------------------------ fonction d' authentification
        function authentifier() {

            let allValid = true;

            let login = $('#login').val();
            let mot_passe = $('#mot_passe').val();


            $('#erreurlogin').text('');
            $('#erreurmotpasse').text('');
            $('#erreurserveur').text('');


            if (login === '') {
                $('#erreurlogin').text("Le login est obligatoire   ");
                allValid = false;

            }



            if (mot_passe === '') {
                $('#erreurmotpasse').text("Le mot de passe est obligatoire   ");
                allValid = false;

            }



            if (allValid) {

                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "{{ route('utilisateur_authenticate') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {

                        login: login,

                        mot_passe: mot_passe,


                    },

                    success: function(data) {


                        console.log(data);



                        // return 0;
                        if (data.code == 1) {
                            location.href = '{{ route('tableau') }}'

                        } else {

                            $('#erreurserveur').text(data.msg);
                            // $('#erreurMotpasse').text(data.data.erreurMotpasse);


                        }

                    },


                    error: function(data) {

                        console.log(data);



                    }



                });

            }
        }
    </script>

</body>



</html>
