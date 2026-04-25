@extends('layout.app')

@section('title')
    Les localisations des élèves
@endsection

@section('titre')
    Les localisations des {{ count($inscriptions) }} élèves
@endsection

@php
    $user_value = session()->get('LoginUser');
    $compte_id = $user_value['compte_id'];
    $user_annee = $user_value['annee_id'];
@endphp

@section('css')
    <link href="{{ asset('admin/css/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

    <link rel="stylesheet" href="{{ asset('admin') }}/vendor/select2/css/select2.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: 600px;
            width: 100%;
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

                    <div id="map"></div>

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


    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Récupère les coordonnées depuis Laravel
        let locations = @json($inscriptions);
        // Exemple : [{"name":"Alice","latitude":6.123,"longitude":1.234}, {...}]
        console.log(locations);

        // Initialise la carte centrée sur la première position
        let map = L.map('map').setView([locations[0].lat, locations[0].lng], 13);

        // Ajoute le fond de carte OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        

        // Ajoute les marqueurs
        locations.forEach(el => {
            L.marker([el.lat, el.lng])
                .addTo(map)
                .bindPopup(`<b>${el.eleve.nom} ${el.eleve.prenom}</b><br>Lat: ${el.lat}<br>Lon: ${el.lng}`);
        });
    </script>
@endsection
