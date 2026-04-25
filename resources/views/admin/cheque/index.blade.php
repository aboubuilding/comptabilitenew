@extends('layout.app')

@section('title')
    Comptabilite | Les chèques
@endsection

@section('css')
    <link href="{{ asset('admin/css/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('nav')
    @include('admin.aside')
@endsection



@section('contenu')
    @php

        $user_value = session()->get('LoginUser');
        $compte_id = $user_value['compte_id'];

        $utilisateur = App\Models\User::rechercheUserById($compte_id);

        $role = $utilisateur->role;
    @endphp

    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            {{-- @if ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN)
            @endif --}}




            <!-- Row -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="row">

                        <div class="col-xl-12">
                            <div class="page-title flex-wrap">

                                <div>

                                    <!-- Button trigger modal -->




                                </div>
                            </div>
                        </div>

                        <!--column-->
                        <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                            <div class="table-responsive full-data">
                                <table
                                    class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                                    id="example">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" class="form-check-input" id="checkAll"
                                                    required="">
                                            </th>
                                            <th>Réference de paiement</th>
                                            <th>Montant paiement</th>
                                            <th>Numéro chèque</th>
                                            <th>Montant du chèque</th>
                                            <th>Elève</th>
                                            <th>Emetteur </th>
                                            <th>Banque </th>
                                            <th>Date emission</th>
                                            <th>Statut du chèque </th>

                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>


                                        @foreach ($data as $cheque)
                                            <tr>
                                                <td>
                                                    <div class="checkbox me-0 align-self-center">
                                                        <div class="custom-control custom-checkbox ">
                                                            <input type="checkbox" class="form-check-input" id="check8"
                                                                required="">
                                                            <label class="custom-control-label" for="check8"></label>
                                                        </div>
                                                    </div>
                                                </td>


                                                <td>
                                                    <div class="trans-list">

                                                        <h4>{{ $cheque['reference'] }}</h4>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h6 class="mb-0">{{ number_format($cheque['montant_paiement']) }}</h6>
                                                </td>
                                                <td>
                                                    <div class="trans-list">

                                                        <h4>{{ $cheque['numero'] }}</h4>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h6 class="mb-0">
                                                        {{ number_format($cheque['montant_cheque']) }}
                                                    </h6>
                                                </td>
                                                <td>
                                                    <h6 class="mb-0">
                                                        {{ $cheque['eleve'] }}
                                                    </h6>
                                                </td>

                                                <td>
                                                    <h6 class="mb-0">{{ $cheque['emetteur'] }} </h6>
                                                </td>
                                                <td>
                                                    <h6 class="mb-0">{{ $cheque['banque'] }} </h6>
                                                </td>

                                                <td>
                                                    <h6 class="mb-0">{{ $cheque['date_emission'] }} </h6>
                                                </td>

                                                <td>

                                                    @if ($cheque['statut'] === \App\Types\StatutPaiement::NON_ENCAISSE)
                                                        <span class="badge badge-primary light badge-sm">Non encaissé <span
                                                                class="ms-1 fa fa-redo"></span></span>
                                                    @endif

                                                    @if ($cheque['statut'] === \App\Types\StatutPaiement::ENCAISSE)
                                                        <span class="badge badge-success light badge-sm">Encaissé <span
                                                                class="ms-1 fa fa-check"></span></span>
                                                    @endif

                                                </td>



                                                <td>
                                                    <div class="d-flex">
                                                        <div class="dropdown custom-dropdown ">
                                                            <div class="btn sharp tp-btn " data-bs-toggle="dropdown">
                                                                <svg width="18" height="6" viewBox="0 0 24 6"
                                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M12.0012 0.359985C11.6543 0.359985 11.3109 0.428302 10.9904 0.561035C10.67 0.693767 10.3788 0.888317 10.1335 1.13358C9.88829 1.37883 9.69374 1.67 9.56101 1.99044C9.42828 2.31089 9.35996 2.65434 9.35996 3.00119C9.35996 3.34803 9.42828 3.69148 9.56101 4.01193C9.69374 4.33237 9.88829 4.62354 10.1335 4.8688C10.3788 5.11405 10.67 5.3086 10.9904 5.44134C11.3109 5.57407 11.6543 5.64239 12.0012 5.64239C12.7017 5.64223 13.3734 5.36381 13.8686 4.86837C14.3638 4.37294 14.6419 3.70108 14.6418 3.00059C14.6416 2.3001 14.3632 1.62836 13.8677 1.13315C13.3723 0.637942 12.7004 0.359826 12 0.359985H12.0012ZM3.60116 0.359985C3.25431 0.359985 2.91086 0.428302 2.59042 0.561035C2.26997 0.693767 1.97881 0.888317 1.73355 1.13358C1.48829 1.37883 1.29374 1.67 1.16101 1.99044C1.02828 2.31089 0.959961 2.65434 0.959961 3.00119C0.959961 3.34803 1.02828 3.69148 1.16101 4.01193C1.29374 4.33237 1.48829 4.62354 1.73355 4.8688C1.97881 5.11405 2.26997 5.3086 2.59042 5.44134C2.91086 5.57407 3.25431 5.64239 3.60116 5.64239C4.30165 5.64223 4.97339 5.36381 5.4686 4.86837C5.9638 4.37294 6.24192 3.70108 6.24176 3.00059C6.2416 2.3001 5.96318 1.62836 5.46775 1.13315C4.97231 0.637942 4.30045 0.359826 3.59996 0.359985H3.60116ZM20.4012 0.359985C20.0543 0.359985 19.7109 0.428302 19.3904 0.561035C19.07 0.693767 18.7788 0.888317 18.5336 1.13358C18.2883 1.37883 18.0937 1.67 17.961 1.99044C17.8283 2.31089 17.76 2.65434 17.76 3.00119C17.76 3.34803 17.8283 3.69148 17.961 4.01193C18.0937 4.33237 18.2883 4.62354 18.5336 4.8688C18.7788 5.11405 19.07 5.3086 19.3904 5.44134C19.7109 5.57407 20.0543 5.64239 20.4012 5.64239C21.1017 5.64223 21.7734 5.36381 22.2686 4.86837C22.7638 4.37294 23.0419 3.70108 23.0418 3.00059C23.0416 2.3001 22.7632 1.62836 22.2677 1.13315C21.7723 0.637942 21.1005 0.359826 20.4 0.359985H20.4012Z"
                                                                        fill="#A098AE" />
                                                                </svg>
                                                            </div>
                                                            @if (
                                                                $cheque['statut'] === \App\Types\StatutPaiement::NON_ENCAISSE &&
                                                                    ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN))
                                                                <div class="dropdown-menu dropdown-menu-end">
                                                                    <a class="dropdown-item encaisser" href=""
                                                                        data-id="{{ $cheque['id'] }}">Encaisser </a>
                                                                    <a class="dropdown-item supprimer" href=""
                                                                        data-id="{{ $cheque['id'] }}">Supprimer </a>

                                                                </div>
                                                            @endif
                                                            @if (
                                                                $cheque['statut'] === \App\Types\StatutPaiement::ENCAISSE &&
                                                                    ($role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN))
                                                                <div class="dropdown-menu dropdown-menu-end">
                                                                    <a class="dropdown-item envoyer_recu" href=""
                                                                        data-id="{{ $cheque['id'] }}">Envoyer reçu</a>
                                                                </div>
                                                            @endif

                                                        </div>
                                                        @if ($cheque['statut'] === \App\Types\StatutPaiement::ENCAISSE)
                                                            <a data-id="{{ $cheque['id'] }}"
                                                                class="btn btn-danger shadow btn-xs sharp imprimer_recu"
                                                                data-id="{{ $cheque['id'] }}" title="Imprimer PDF  "><i
                                                                    class="fa fa-print"></i></a>
                                                        @endif
                                                    </div>
                                                    {{-- @if ($cheque['statut'] === \App\Types\StatutPaiement::ENCAISSE)
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <a class="dropdown-item imprimer_recu" href=""
                                                                data-id="{{ $cheque['id'] }}">Imprimer reçu</a>
                                                        </div>
                                                    @endif --}}
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
@endsection



@section('include')
@endsection


@section('js')
    <!-- Datatable -->
    <script src="{{ asset('admin') }}/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('admin') }}/js/plugins-init/datatables.init.js"></script>
    <!-- Apex Chart -->

    <!-- Dashboard 1 -->
    <script src="{{ asset('admin') }}/js/dashboard/dashboard-2.js"></script>
    <script src="{{ asset('admin') }}/vendor/chart.js/Chart.bundle.min.js"></script>
    <!-- Apex Chart -->
    <script src="{{ asset('admin') }}/vendor/apexchart/apexchart.js"></script>

    <!-- Chart piety plugin files -->

    <script src="{{ asset('admin') }}/vendor/jquery-nice-select/js/jquery.nice-select.min.js"></script>

    <script src="{{ asset('admin') }}/vendor/wow-master/dist/wow.min.js"></script>


    <script src="{{ asset('admin/js/sweetalert2/sweetalert2.min.js') }}"></script>



    <script>
        jQuery(document).ready(function() {


            $(document).on('click', '.envoyer_recu', function(e) {

                e.preventDefault();

                let id = $(this).data('id');

                let url = "/cheques/sendFacture/" + id;

                // console.log(url);

                $.ajax({
                    type: 'POST',
                    url: url,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        console.log(data);
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.msg,
                            showConfirmButton: false,
                        });
                    },
                    error: function(data) {
                        console.log(data);
                    }
                });


            });


            $(document).on('click', '.imprimer_recu', function(e) {

                e.preventDefault();

                let id = $(this).data('id');

                let url = "/cheques/imprimerFacture/" + id;

                window.open(url, '_blank');

            });

            //------------------------ Afficher le popup de suppression
            $(document).on('click', '.encaisser', function(e) {

                e.preventDefault();

                let id = $(this).data('id');

                encaisser(id);
            });

            $(document).on('click', '.supprimer', function(e) {

                e.preventDefault();

                let id = $(this).data('id');

                supprimer(id);
            });

        });





        //------------------------ Update de Niveau
        function encaisser(id) {
            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: "/cheques/encaisser",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    id: id
                },

                success: function(data) {


                    console.log(data);

                    Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
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

        function supprimer(id) {
            Swal.fire({
                title: 'Êtes-vous sûr de vouloir supprimer ce chèque ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, supprimer !',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        dataType: 'json',
                        type: 'POST',
                        url: "/cheques/supprimer",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            id: id
                        },

                        success: function(data) {
                            console.log(data);
                            Swal.fire(
                                'Supprimé !',
                                data.msg,
                                'success'
                            );

                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        },

                        error: function(data) {
                            console.log(data);
                        }
                    });
                }
            });
        }
    </script>
@endsection
