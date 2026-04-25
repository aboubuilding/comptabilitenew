@extends('layout.app')

@section('title')
    Comptabilité | Détail de caisse
@endsection

@section('titre')
    Détail de la caisse {{ $caisse->libelle }}
@endsection
@php

    $user_value = session()->get('LoginUser');
    $compte_id = $user_value['compte_id'];

    $user = App\Models\User::rechercheUserById($compte_id);

@endphp


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
                        <div class="card-body pb-xl-4 pb-sm-3 pb-0">
                            <div class="row">
                                <div class="col-xl-4 col-6">
                                    <div class="content-box">
                                        <div class="icon-box icon-box-xl std-data">
                                            <svg width="25" height="25" viewBox="0 0 30 38" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M12.9288 37.75H3.75C1.67875 37.75 0 36.0713 0 34V23.5863C0 21.7738 1.29625 20.2213 3.07875 19.8975C5.72125 19.4163 10.2775 18.5875 12.855 18.12C14.2737 17.8612 15.7263 17.8612 17.145 18.12C19.7225 18.5875 24.2788 19.4163 26.9213 19.8975C28.7038 20.2213 30 21.7738 30 23.5863C30 26.3125 30 31.0825 30 34C30 36.0713 28.3212 37.75 26.25 37.75H12.9288ZM24.785 22.05L24.79 22.0563C25.0088 22.3838 25.06 22.795 24.9287 23.1662L24.0462 25.6662C23.9312 25.9925 23.685 26.2575 23.3675 26.3963L21.7075 27.12L22.3675 28.4412C22.5525 28.81 22.5425 29.2462 22.3425 29.6075L19.2075 35.25H26.25C26.94 35.25 27.5 34.69 27.5 34C27.5 31.0825 27.5 26.3125 27.5 23.5863C27.5 22.9825 27.0675 22.465 26.4738 22.3562L24.785 22.05ZM21.3663 21.4275L16.6975 20.5788C15.575 20.375 14.425 20.375 13.3025 20.5788L8.63375 21.4275L7.63625 22.9238L8.13 24.3213L10.5 25.3537C10.8138 25.4912 11.0575 25.7512 11.175 26.0737C11.2925 26.3962 11.2712 26.7525 11.1175 27.0588L10.1625 28.9688L13.6525 35.25H16.3475L19.8375 28.9688L18.8825 27.0588C18.7288 26.7525 18.7075 26.3962 18.825 26.0737C18.9425 25.7512 19.1862 25.4912 19.5 25.3537L21.87 24.3213L22.3638 22.9238L21.3663 21.4275ZM5.215 22.05L3.52625 22.3562C2.9325 22.465 2.5 22.9825 2.5 23.5863V34C2.5 34.69 3.06 35.25 3.75 35.25H10.7925L7.6575 29.6075C7.4575 29.2462 7.4475 28.81 7.6325 28.4412L8.2925 27.12L6.6325 26.3963C6.315 26.2575 6.06875 25.9925 5.95375 25.6662L5.07125 23.1662C4.94 22.795 4.99125 22.3838 5.21 22.0563L5.215 22.05ZM23.75 29V31.5C23.75 32.19 24.31 32.75 25 32.75C25.69 32.75 26.25 32.19 26.25 31.5V29C26.25 28.31 25.69 27.75 25 27.75C24.31 27.75 23.75 28.31 23.75 29ZM15 0.25C10.5163 0.25 6.875 3.89125 6.875 8.375C6.875 12.8587 10.5163 16.5 15 16.5C19.4837 16.5 23.125 12.8587 23.125 8.375C23.125 3.89125 19.4837 0.25 15 0.25ZM15 2.75C18.105 2.75 20.625 5.27 20.625 8.375C20.625 11.48 18.105 14 15 14C11.895 14 9.375 11.48 9.375 8.375C9.375 5.27 11.895 2.75 15 2.75Z"
                                                    fill="white" />
                                            </svg>
                                        </div>
                                        <div class="chart-num">
                                            <p>Responsable </p>
                                            <h2 class="font-w200 mb-0">
                                                {{ $caisse->responsable->nom . ' ' . $caisse->responsable->prenom }}</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-6">
                                    <div class="content-box">
                                        <div class="teach-data icon-box icon-box-xl">
                                            <svg width="25" height="25" viewBox="0 0 30 38" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M12.9288 37.75H3.75C1.67875 37.75 0 36.0713 0 34V23.5863C0 21.7738 1.29625 20.2213 3.07875 19.8975C5.72125 19.4163 10.2775 18.5875 12.855 18.12C14.2737 17.8612 15.7263 17.8612 17.145 18.12C19.7225 18.5875 24.2788 19.4163 26.9213 19.8975C28.7038 20.2213 30 21.7738 30 23.5863C30 26.3125 30 31.0825 30 34C30 36.0713 28.3212 37.75 26.25 37.75H12.9288ZM24.785 22.05L24.79 22.0563C25.0088 22.3838 25.06 22.795 24.9287 23.1662L24.0462 25.6662C23.9312 25.9925 23.685 26.2575 23.3675 26.3963L21.7075 27.12L22.3675 28.4412C22.5525 28.81 22.5425 29.2462 22.3425 29.6075L19.2075 35.25H26.25C26.94 35.25 27.5 34.69 27.5 34C27.5 31.0825 27.5 26.3125 27.5 23.5863C27.5 22.9825 27.0675 22.465 26.4738 22.3562L24.785 22.05ZM21.3663 21.4275L16.6975 20.5788C15.575 20.375 14.425 20.375 13.3025 20.5788L8.63375 21.4275L7.63625 22.9238L8.13 24.3213L10.5 25.3537C10.8138 25.4912 11.0575 25.7512 11.175 26.0737C11.2925 26.3962 11.2712 26.7525 11.1175 27.0588L10.1625 28.9688L13.6525 35.25H16.3475L19.8375 28.9688L18.8825 27.0588C18.7288 26.7525 18.7075 26.3962 18.825 26.0737C18.9425 25.7512 19.1862 25.4912 19.5 25.3537L21.87 24.3213L22.3638 22.9238L21.3663 21.4275ZM5.215 22.05L3.52625 22.3562C2.9325 22.465 2.5 22.9825 2.5 23.5863V34C2.5 34.69 3.06 35.25 3.75 35.25H10.7925L7.6575 29.6075C7.4575 29.2462 7.4475 28.81 7.6325 28.4412L8.2925 27.12L6.6325 26.3963C6.315 26.2575 6.06875 25.9925 5.95375 25.6662L5.07125 23.1662C4.94 22.795 4.99125 22.3838 5.21 22.0563L5.215 22.05ZM23.75 29V31.5C23.75 32.19 24.31 32.75 25 32.75C25.69 32.75 26.25 32.19 26.25 31.5V29C26.25 28.31 25.69 27.75 25 27.75C24.31 27.75 23.75 28.31 23.75 29ZM15 0.25C10.5163 0.25 6.875 3.89125 6.875 8.375C6.875 12.8587 10.5163 16.5 15 16.5C19.4837 16.5 23.125 12.8587 23.125 8.375C23.125 3.89125 19.4837 0.25 15 0.25ZM15 2.75C18.105 2.75 20.625 5.27 20.625 8.375C20.625 11.48 18.105 14 15 14C11.895 14 9.375 11.48 9.375 8.375C9.375 5.27 11.895 2.75 15 2.75Z"
                                                    fill="white" />
                                            </svg>
                                        </div>
                                        <div class="chart-num">
                                            <p>Créé par </p>
                                            <h2 class="font-w200 mb-0">
                                                {{ $caisse->utilisateur->nom . ' ' . $caisse->utilisateur->prenom }}</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-6">
                                    <div class="content-box">

                                        <div class="chart-num">
                                            <p>Statut </p>
                                            @if ($caisse->statut == \App\Types\StatutCaisse::OUVERT)
                                                <span class="badge badge-rounded badge-success">Ouvert </span>
                                            @endif

                                            @if ($caisse->statut == \App\Types\StatutCaisse::CLOTURE)
                                                <span class="badge badge-rounded badge-danger">Cloturé </span>
                                            @endif

                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($caisse->statut == \App\Types\StatutCaisse::OUVERT)
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="col-xl-12">
                                <div class="page-title flex-wrap">
                                    @php
                                        $disabled = 'disabled';
                                    @endphp
                                    <div>

                                        <button type="button" class="btn btn-success" id="lancerDecaissement"
                                            @if ($caisse->responsable->id !== $user->id) {{ $disabled }} @endif>
                                            + Décaissement
                                        </button>

                                        <!-- Button trigger modal -->
                                        <button type="button" class="btn btn-success" id="lancerEncaissement"
                                            data-id="{{ $caisse->id }}"
                                            @if ($caisse->responsable->id !== $user->id) {{ $disabled }} @endif>
                                            + Encaissement
                                        </button>


                                        <button type="button" class="btn btn-primary" id="cloturerCaisse"
                                            data-id="{{ $caisse->id }}"
                                            @if ($caisse->responsable->id !== $user->id) {{ $disabled }} @endif>
                                            + Cloturer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif


            <div class="row">
                <div class="col-xl-12">
                    <div class="card">


                        <div class="custom-tab-1">
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#encaissement"><i
                                            class="la la-home me-2"></i> Encaissements


                                        <span
                                            class="badge badge-rounded badge-success">{{ number_format($total_encaissements, 0, ',', ' ') }}
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#decaissement"><i
                                            class="la la-user me-2"></i> Décaissements

                                        <span
                                            class="badge badge-rounded badge-success">{{ number_format($total_decaissements, 0, ',', ' ') }}
                                        </span>
                                    </a>
                                </li>

                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="encaissement" role="tabpanel">
                                    <div class="row" style="margin: 20px">
                                        <div class="col-xl-12">

                                            <br>
                                            <a href="{{ url('/caisses/journaltotal/' . $caisse['id']) }}" target="_blank"
                                                type="button" href="#" id=""
                                                class="btn btn-rounded btn-info"><span class="btn-icon-start text-info"><i
                                                        class="fa fa-file color-info"></i>


                                                </span>Journal totaux </a>


                                            <a href="{{ url('/caisses/journaldetails/' . $caisse['id']) }}" target="_blank"
                                                type="button" href="#" id=""
                                                class="btn btn-rounded btn-info"><span class="btn-icon-start text-info"><i
                                                        class="fa fa-file color-info"></i>


                                                </span>Journal details </a>

                                            <br>
                                            <div class="row">



                                                <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                                                    <div class="table-responsive full-data">
                                                        <table
                                                            class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                                                            id="example">
                                                            <thead>
                                                                <tr>
                                                                    <th>
                                                                        <input type="checkbox" class="form-check-input"
                                                                            id="checkAll" required="">
                                                                    </th>
                                                                    <th>Réference </th>
                                                                    <th>Nom et prénom </th>
                                                                    <th>Date paiement</th>
                                                                    <th>Date encaissement </th>
                                                                    <th>Montant </th>


                                                                    <th class="">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>


                                                                @foreach ($data_encaissements as $encaissement)
                                                                    <tr>
                                                                        <td>
                                                                            <div class="checkbox me-0 align-self-center">
                                                                                <div
                                                                                    class="custom-control custom-checkbox ">
                                                                                    <input type="checkbox"
                                                                                        class="form-check-input"
                                                                                        id="check8" required="">
                                                                                    <label class="custom-control-label"
                                                                                        for="check8"></label>
                                                                                </div>
                                                                            </div>
                                                                        </td>


                                                                        <td>
                                                                            <div class="trans-list">

                                                                                <h4>{{ $encaissement['reference'] }}</h4>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="trans-list">

                                                                                <h4>{{ $encaissement['eleve'] }}</h4>
                                                                            </div>
                                                                        </td>


                                                                         <td>
                                                                            <h6 class="mb-0">
                                                                                {{ $encaissement['date_paiement'] }}
                                                                            </h6>
                                                                        </td>
                                                                        <td>
                                                                            <h6 class="mb-0">
                                                                                {{ $encaissement['date_operation'] }}
                                                                            </h6>
                                                                        </td>




                                                                        <td>



                                                                            <span
                                                                                class="badge badge-rounded badge-success">



                                                                                {{ $encaissement['montant'] }}


                                                                            </span>



                                                                        </td>




                                                                        <td>


                                                                            <div class="d-flex">


                                                                                <a href="{{ route('encaissement_pdf', ['id' => $encaissement['id']]) }}"
                                                                                    class="btn btn-success shadow btn-xs sharp me-1 "
                                                                                    target="_blank"
                                                                                    style="background-color:
                                                                                    #1EA1F3; border: red"
                                                                                    title="Imprimer l encaissement  "><i
                                                                                        class="fa fa-print"></i></a>




                                                                                <a href="#"
                                                                                    class="btn btn-primary shadow btn-xs sharp me-1 supprimerCaisse"
                                                                                    style="background-color: green; border: #1EA1F3"
                                                                                    data-id="{{ $encaissement['id'] }}"
                                                                                    title="Voir detail  "><i
                                                                                        class="fa fa-eye"></i></a>


                                                                                @if ($user->role == App\Types\Role::ADMIN)
                                                                                    
                                                                                    <a href=""
                                                                                        class="btn btn-primary shadow btn-xs sharp me-1 deleteEncaissement"
                                                                                        style="background-color: red; border: #1EA1F3"
                                                                                        data-id="{{ $encaissement['id'] }}"
                                                                                        title="Supprimer l encaissement  "><i
                                                                                            class="fa fa-trash"></i></a>
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
                                <hr>
                                <div class="tab-pane fade" id="decaissement">
                                    <div class="row" style="margin: 20px">
                                        <div class="col-xl-12">
                                            <br>
                                            <br>


                                            <div class="row">



                                                <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                                                    <div class="table-responsive full-data">
                                                        <table
                                                            class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                                                            id="example">
                                                            <thead>
                                                                <tr>
                                                                    <th>
                                                                        <input type="checkbox" class="form-check-input"
                                                                            id="checkAll" required="">
                                                                    </th>
                                                                    <th>Réference </th>
                                                                    <th>Beneficaire </th>

                                                                    <th>Date operation </th>
                                                                    <th>Montant </th>


                                                                    <th class="text-end">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>


                                                                @foreach ($data_decaissements as $decaissement)
                                                                    <tr>
                                                                        <td>
                                                                            <div class="checkbox me-0 align-self-center">
                                                                                <div
                                                                                    class="custom-control custom-checkbox ">
                                                                                    <input type="checkbox"
                                                                                        class="form-check-input"
                                                                                        id="check8" required="">
                                                                                    <label class="custom-control-label"
                                                                                        for="check8"></label>
                                                                                </div>
                                                                            </div>
                                                                        </td>


                                                                        <td>
                                                                            <div class="trans-list">

                                                                                <h4>{{ $decaissement['reference'] }}</h4>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="trans-list">

                                                                                <h4>{{ $decaissement['beneficiaire'] }}
                                                                                </h4>
                                                                            </div>
                                                                        </td>



                                                                        <td>
                                                                            <h6 class="mb-0">
                                                                                {{ $decaissement['date_operation'] }}
                                                                            </h6>
                                                                        </td>




                                                                        <td>



                                                                            <span
                                                                                class="badge badge-rounded badge-success">{{ $decaissement['montant'] }}
                                                                            </span>



                                                                        </td>




                                                                        <td>



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
                        </div>

                    </div>
                </div>
            </div>



        </div>
    </div>

    @include('admin.caisse.modalencaissement')
    @include('admin.caisse.modal')
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





            //--------------------------------- changement de choix de l eleve


            $("#paiement_id").on("change", function(event) {

                event.preventDefault();

                let paiement_id = parseInt($('#paiement_id').val());


                chargerPaiement(paiement_id);


            });





            //Lancer un encaissement

            $("#lancerEncaissement").click(function(event) {
                event.preventDefault();

                let id = $(this).data('id');
                lancerEncaissement(id)
            });


            $("#annulerEncaissement").click(function(event) {
                event.preventDefault();


                annulerEncaissement()
            });




            //Lancer un decaissement
            $("#lancerDecaissement").click(function(event) {
                event.preventDefault();


                lancerDecaissement()
            });

            //Annuler  un encaissement

            $("#addCaisse #annulerCaisse").click(function(event) {
                event.preventDefault();

                annulerCloture()
            });


            //Annuler  un decaissement

            $("#annulerDecaissement").click(function(event) {
                event.preventDefault();

                annulerDecaissement()
            });


            //Annuler  un encaissement
            $(document).on('click', '#addEncaissement  #ajouterEncaissement', function(event) {

                event.preventDefault();
                let id = $(this).data('id');


                event.preventDefault();
                validerEncaissement()

            });


            //Ajouter   un decaissement
            $(document).on('click', '#ajouterDecaissement', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                event.preventDefault();
                validerDecaissement()

            });

            $(document).on('click', '.deleteEncaissement', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                Swal.fire({
                    title: 'Êtes-vous sûr ?',
                    text: "Vous ne pourrez pas revenir en arrière !",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Oui, supprimer !'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: '/encaissements/supprimer/' + id,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Supprimé !',
                                    'L encaissement a été supprimé.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                console.error(xhr);
                                Swal.fire(
                                    'Erreur !',
                                    'Une erreur s\'est produite lors de la suppression.',
                                    'error'
                                );
                            }
                        });
                    }
                }); 

            });


            //------------------------ Cloturer une caisse
            $(document).on('click', '#cloturerCaisse', function(event) {



                event.preventDefault();
                clearData();

                let id = $(this).data('id');
                let url = "/caisses/modifier/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#addCaisse #defaultModalLabel').text('Cloturer une caisse ');

                    let caisse_modal = $('#addCaisse');

                    // Données sur l eleve

                    $(caisse_modal).find('form').find('input[name="libelle"]').val(data.caisse
                        .libelle);
                    $(caisse_modal).find('form').find('input[name="solde_initial"]').val(data.caisse
                        .solde_initial);
                    $(caisse_modal).find('form').find('input[name="solde_final"]').val(data.caisse
                        .solde_final);



                    // Données sur l' inscription
                    $(caisse_modal).find('form').find('select[name="responsable_id"]').val(data
                        .caisse.responsable_id);

                    $('#solde_initial').prop('disabled', true);
                    $('#libelle').prop('disabled', true);




                    $('#idCaisse').val(data.caisse.id);



                    $("#div_cloturer").show();
                    $("#ajouterCaisse").hide();
                    $("#updateCaisse").hide();
                    $("#validerCloture").show();
                    $("#annulerCaisse").show();

                    $(caisse_modal).modal('toggle');

                }, 'json')



            });



            $("#validerCloture").click(function(event) {
                event.preventDefault();

                validerCloture()
            });





            clearData();
            clearDataEncaissement();

        });



        function clearData() {

            $('#libelle').val('');
            $('#solde_initial').val('');
            $('#solde_final').val('');


            let form = document.getElementById('form');
            $(form).find('span.error-text').text('');




        }


        function clearDataEncaissement() {

            $('#payeur').val('');
            $('#eleve').val('');
            $('#niveau').val('');
            $('#montant').val('');
            $('#date_paiement').val('');
            $('#paiement_id').val('');

            $('#liste_details').empty();

            $("#ajouterEncaissement").attr("disabled", false);

            let form = document.getElementById('formcaisse');
            $(form).find('span.error-text').text('');

            $("#ajouterEncaissement").show();
            $("#updateEncaissement").hide();


        }





        //------------------------ Valider la catégorie

        function validerEncaissement() {

            let allValid = true;
            let paiement_id = parseInt($("#paiement_id").val(), 10);
            let montant_paiement = parseInt($("#montant").val(), 10);
            let montant_encaisse = parseInt($("#montant_encaisse").val(), 10);

            let remise = parseInt($("#remise").val(), 10) || 0;

            let caisse_id = parseInt($("#idCaisseouvert").val(), 10);

            if (isNaN(paiement_id) || paiement_id === 0) {
                $('.paiement_id_error').text("Le choix du paiement      est obligatoire ");
                allValid = false;

            }

            if (isNaN(montant_encaisse)) {
                $('.montant_encaisse_error').text("Le montant  encaissé   est obligatoire ");
                allValid = false;

            }

            if (isNaN(montant_paiement)) {
                $('.montant_paiement_error').text("Le montant    du paiement    est obligatoire ");
                allValid = false;

            }

            if (montant_paiement != montant_encaisse) {
                $('.montant_encaisse_error').text("Le montant encaissé  doit etre equivalent au montant du paiement ");
                allValid = false;

            }



            // Ajout des donnees de  produits
            let list_detail_paiements = [];


            $('#liste_details  tr').each(function() {


                var detail_paiement = {

                    id: $(this).find("td:eq(0)").attr('data-id'),



                }


                list_detail_paiements.push(detail_paiement);


            });

            if (list_detail_paiements.length == 0) {
                $('.montant_encaisse_error').text("Le paiement n ' as pas de details ");
                allValid = false;


            }



            if (allValid) {
                $("#ajouterEncaissement").attr("disabled", true);
                let form = document.getElementById('formcaisse');
                let formData = new FormData(form);

                formData.append('caisse_id', caisse_id);
                formData.append('taux_remise', remise);


                if (list_detail_paiements.length) {



                    for (var i = 0; i < list_detail_paiements.length; i++) {
                        formData.append('ligne_details[' + i + '][id]', list_detail_paiements[i].id);


                    }

                }




                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/encaissements/save",
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

                        clearDataEncaissement




                        let id = data.id;

                        // let url = "/encaissements/pdf/" + id;

                        // window.location.href = url;

                        location.reload();


                    },

                    error: function(data) {

                        console.log(data);



                    }



                });



            }
        }




        //------------------------ Ouvrir le popup d' ajout
        function lancerEncaissement(id) {

            clearDataEncaissement();


            $('#idCaisseouvert').val(id);

            $('#defaultModalLabel').text('Ajouter  un encaissement    ');





            $('#addEncaissement').modal('toggle');
        }


        //------------------------ Fermer  le popup d' ajout
        function annulerEncaissement() {

            clearData();

            $('#addEncaissement').modal('toggle');
        }




        //------------------------ Fermer  le popup d' ajout
        function annulerCloture() {

            clearData();

            $('#addCaisse').modal('toggle');
        }




        //------------------------  Charger les frais de scolarite
        function chargerPaiement(paiement_id) {

            $.ajax({
                dataType: 'json',
                type: 'GET',
                url: "/paiements/charger/" + paiement_id,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },


                data: {


                },


                success: function(data) {

                    console.log(data);




                    $('#payeur').val(data.payeur);
                    $('#eleve').val(data.eleve);
                    $('#niveau').val(data.niveau);
                    $('#montant').val(data.montant);
                    $('#remise').val(data.remise);
                    $('#montant_encaisse').val();


                    $('#liste_details').empty();
                    let output = '';
                    let index = 0;
                    let details = data.data;

                    for (let i = 0; i < details.length; i++) {
                        index += 1;
                        output += `
                            <tr >
                            <td data-id="${details[i].id}">${index}</td>
                            <td>${details[i].libelle}</td>
                            <td>${details[i].montant}</td>
                            <td>${details[i].type_paiement}</td>

                            `;

                    }


                    $('#liste_details').append(output);



                },

                error: function(data) {

                    console.log(data);



                }



            });


        }


        //------------------------ Cloturer la caisse
        function validerCloture() {



            let allValid = true;

            let solde_final = parseInt($("#solde_final").val(), 10);




            let id = $('#idCaisse').val();


            if (isNaN(solde_final) || solde_final === 0) {
                $('.solde_final_error').text("Le solde final   est obligatoire ");
                allValid = false;

            }



            if (allValid) {

                let form = document.getElementById('form');
                let formData = new FormData(form);




                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "/caisses/cloturer/" + id,
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
    </script>
@endsection
