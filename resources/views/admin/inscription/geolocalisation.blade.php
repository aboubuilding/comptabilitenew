@extends('layout.app')

@section('title')
    Prendre la géolocalisation
@endsection

@section('titre')
    Prendre la géolocalisation
@endsection

@php
    $user_value = session()->get('LoginUser');
    $compte_id = $user_value['compte_id'];
    $user_annee = $user_value['annee_id'];
@endphp

@section('css')
    <link href="{{ asset('admin/css/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

    <link rel="stylesheet" href="{{ asset('admin') }}/vendor/select2/css/select2.min.css">
    <style>
        #loading {
            display: none;
            font-size: 18px;
            color: blue;
        }
    </style>
@endsection

@section('nav')
    @include('admin.aside')
@endsection



@section('contenu')
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            <div class="card">
                <div class="card-body pb-xl-4 pb-sm-3 pb-0">

                    <form method="post" action="#" enctype="multipart/form-data" id="form">

                        @csrf

                        <div class="row">

                            <h3 class="mb-0" style="text-transform: uppercase">Choix de l'élève </h3>
                            <hr>

                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label class="form-label d-block">Eleves </label>
                                    <select class="single-select col-xl-12" id="single-select" name="inscription_id">
                                        <option selected value="">Choisir un élève </option>





                                        @foreach ($inscriptions as $inscription)
                                            
                                                <option value="{{ $inscription->id }}"
                                                    data-inscription="{{ $inscription }}">
                                                    {{ $inscription->eleve->nom . ' ' . $inscription->eleve->prenom . ' | ' . $inscription->cycle->libelle }}
                                                </option>
                                        
                                        @endforeach



                                    </select>

                                </div>

                                <span class="text-danger error-text single-select_error"> </span>

                            </div>


                        </div>

                        <br>
                        <p id="outputOld"></p>
                        <br>
                        <hr>
                        <div class="row">
                            <h3 class="mb-0" style="text-transform: uppercase">Géolocalisation </h3>
                            <hr>
                            <p id="output"></p>
                            <p id="loading"><i class="fa fa-spinner fa-spin" style="font-size:24px"></i> Recherche de position précise... </p>
                            <div class="">
                                <button id="getLocationBtn" class="btn btn-secondary" type="button"
                                    onclick="getLocation()">📍 Prendre la
                                    localisation</button>

                                <button style="float: right" id="saveLocationBtn" class="btn btn-primary" type="button"
                                    onclick="saveLocation()">💾 Enregistrer la localisation</button>

                            </div>
                        </div>

                    </form>

                </div>
            </div>




        </div>
    </div>
@endsection





@section('js')
    <!-- Datatable -->
    <script src="{{ asset('admin') }}/vendor/select2/js/select2.full.min.js"></script>
    <script src="{{ asset('admin') }}/js/plugins-init/select2-init.js"></script>
    <script src="{{ asset('admin') }}/vendor/select2/js/select2.full.min.js"></script>
    <script src="{{ asset('admin') }}/js/plugins-init/select2-init.js"></script>


    <script src="{{ asset('admin/js/sweetalert2/sweetalert2.min.js') }}"></script>


    <script>
        jQuery(document).ready(function() {

            $("#saveLocationBtn").attr("disabled", true);

            $('#single-select').on('change', function() {

                var inscription = $(this).find(':selected').data('inscription');
                document.getElementById("output").innerText = "";
                document.getElementById("outputOld").innerText = "";
                if (inscription.lat && inscription.lng) {
                    $('#outputOld').text('Les anciennes coordonnées sont - Lat: ' + inscription.lat +
                        ' | Lon: ' + inscription.lng);
                }

                // console.log({inscription});
            });

        });
    </script>
    <script>
        let watchId = null;
        let bestAccuracy = Infinity;
        let bestPosition = null;
        let lastImprovementTime = null;

        function getLocation() {
            if (!navigator.geolocation) {
                document.getElementById("output").innerText =
                    "La géolocalisation n'est pas supportée par ce navigateur.";
                return;
            }

            let inscription = $('#single-select').find(':selected').data('inscription');

            if (!inscription) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Veuillez sélectionner un élève.',
                });
                return;
            }

            bestAccuracy = Infinity;
            bestPosition = null;
            lastImprovementTime = Date.now();

            document.getElementById("loading").style.display = "block";
            document.getElementById("output").innerText = "";

            watchId = navigator.geolocation.watchPosition(
                processPosition,
                showError, {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 0
                }
            );
        }

        function processPosition(position) {
            let latitude = position.coords.latitude;
            let longitude = position.coords.longitude;
            let accuracy = position.coords.accuracy; // en mètres

            document.getElementById("output").innerText =
                "Lat: " + latitude +
                " | Lon: " + longitude +
                " | Précision: " + accuracy + "m" +
                " | Meilleure: " + (bestAccuracy === Infinity ? "..." : bestAccuracy + "m");

            // Si meilleure précision trouvée
            if (accuracy < bestAccuracy) {
                bestAccuracy = accuracy;
                bestPosition = position;
                lastImprovementTime = Date.now();
            }

            // Si pas d’amélioration depuis 5 secondes → on arrête
            if ((Date.now() - lastImprovementTime > 5000) || bestAccuracy <= 10) {
                finishTracking();
            }
        }

        function finishTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }

            document.getElementById("loading").style.display = "none";

            if (bestPosition) {
                $("#lat").val(bestPosition.coords.latitude);
                $("#lng").val(bestPosition.coords.longitude);
                $("#saveLocationBtn").attr("disabled", false);
                
            }
        }

        function showError(error) {
            document.getElementById("loading").style.display = "none";
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    alert("Permission refusée");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Position non disponible");
                    break;
                case error.TIMEOUT:
                    alert("Délai dépassé");
                    break;
                default:
                    alert("Erreur inconnue");
            }
        }


        function saveLocation() {
            let inscription = $('#single-select').find(':selected').data('inscription');

            if (!inscription) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Veuillez sélectionner un élève.',
                });
                return;
            }

            if (!bestPosition) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Aucune position précise disponible. Veuillez réessayer.',
                });
                return;
            }

            fetch("/inscriptions/save-location", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        inscription_id: inscription.id,
                        lat: bestPosition.coords.latitude,
                        lng: bestPosition.coords.longitude,
                        accuracy: bestAccuracy
                    })
                })
                .then(res => res.json())
                .then(data => {
                    console.log("Réponse du serveur:", data);
                    if (data.code === 0) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: 'Localisation enregistrée avec succès !',
                        });
                        // Réinitialiser l'état
                        bestPosition = null;
                        bestAccuracy = Infinity;
                        $("#saveLocationBtn").attr("disabled", true);
                        document.getElementById("output").innerText = "";
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: data.message || 'Une erreur est survenue lors de l\'enregistrement.',
                        });
                    }
                })
                .catch(err => {
                    console.error("Erreur:", err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Une erreur est survenue lors de l\'enregistrement.',
                    });
                });
        }
    </script>

    {{-- <script>
        let watchId = null;

        function getLocation() {
            if (!navigator.geolocation) {
                document.getElementById("output").innerText =
                    "La géolocalisation n'est pas supportée par ce navigateur.";
                return;
            }

            document.getElementById("loading").style.display = "block";
            document.getElementById("output").innerText = "";

            // Utilisation de watchPosition pour suivre jusqu’à précision suffisante
            watchId = navigator.geolocation.watchPosition(
                savePosition,
                showError, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function savePosition(position) {
            let latitude = position.coords.latitude;
            let longitude = position.coords.longitude;
            let accuracy = position.coords.accuracy; // en mètres

            document.getElementById("output").innerText =
                "Lat: " + latitude +
                " | Lon: " + longitude +
                " | Précision: " + accuracy + "m";

            // ✅ Si précision < 20m → on arrête le loading et le suivi
            if (accuracy <= 20) {
                document.getElementById("loading").style.display = "none";

                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                }

                // 👉 Ici tu peux envoyer au backend Laravel
                fetch("/save-location", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            latitude: latitude,
                            longitude: longitude,
                            accuracy: accuracy
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("Localisation enregistrée avec succès !");
                        }
                    })
                    .catch(err => console.error("Erreur:", err));
            }
        }

        function showError(error) {
            document.getElementById("loading").style.display = "none";
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    alert("Permission refusée");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Position non disponible");
                    break;
                case error.TIMEOUT:
                    alert("Délai dépassé");
                    break;
                default:
                    alert("Erreur inconnue");
            }
        }
    </script> --}}

    {{-- <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    savePosition,
                    showError, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                document.getElementById("output").innerHTML =
                    "La géolocalisation n'est pas supportée par ce navigateur.";
            }
        }

        function savePosition(position) {
            let latitude = position.coords.latitude;
            let longitude = position.coords.longitude;
            let accuracy = position.coords.accuracy;

            document.getElementById("output").innerHTML =
                "Latitude: " + latitude + "<br>Longitude: " + longitude + "<br>Précision: " + accuracy + "m";

           
        }

        function showError(error) {
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    alert("L'utilisateur a refusé la demande de géolocalisation.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Les informations de localisation ne sont pas disponibles.");
                    break;
                case error.TIMEOUT:
                    alert("La demande de localisation a expiré.");
                    break;
                default:
                    alert("Une erreur inconnue est survenue.");
            }
        }
    </script> --}}
@endsection
