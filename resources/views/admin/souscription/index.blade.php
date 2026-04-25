
@extends('layout.app')

@section('title')


    Comptabilite | Toutes les souscriptions
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


    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body pb-xl-4 pb-sm-3 pb-0">
                    <div class="row">
                        <div class="col-xl-4 col-6">
                            <div class="content-box">
                                <div class="icon-box icon-box-xl std-data">
                                    <svg width="25" height="25" viewBox="0 0 30 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.9288 37.75H3.75C1.67875 37.75 0 36.0713 0 34V23.5863C0 21.7738 1.29625 20.2213 3.07875 19.8975C5.72125 19.4163 10.2775 18.5875 12.855 18.12C14.2737 17.8612 15.7263 17.8612 17.145 18.12C19.7225 18.5875 24.2788 19.4163 26.9213 19.8975C28.7038 20.2213 30 21.7738 30 23.5863C30 26.3125 30 31.0825 30 34C30 36.0713 28.3212 37.75 26.25 37.75H12.9288ZM24.785 22.05L24.79 22.0563C25.0088 22.3838 25.06 22.795 24.9287 23.1662L24.0462 25.6662C23.9312 25.9925 23.685 26.2575 23.3675 26.3963L21.7075 27.12L22.3675 28.4412C22.5525 28.81 22.5425 29.2462 22.3425 29.6075L19.2075 35.25H26.25C26.94 35.25 27.5 34.69 27.5 34C27.5 31.0825 27.5 26.3125 27.5 23.5863C27.5 22.9825 27.0675 22.465 26.4738 22.3562L24.785 22.05ZM21.3663 21.4275L16.6975 20.5788C15.575 20.375 14.425 20.375 13.3025 20.5788L8.63375 21.4275L7.63625 22.9238L8.13 24.3213L10.5 25.3537C10.8138 25.4912 11.0575 25.7512 11.175 26.0737C11.2925 26.3962 11.2712 26.7525 11.1175 27.0588L10.1625 28.9688L13.6525 35.25H16.3475L19.8375 28.9688L18.8825 27.0588C18.7288 26.7525 18.7075 26.3962 18.825 26.0737C18.9425 25.7512 19.1862 25.4912 19.5 25.3537L21.87 24.3213L22.3638 22.9238L21.3663 21.4275ZM5.215 22.05L3.52625 22.3562C2.9325 22.465 2.5 22.9825 2.5 23.5863V34C2.5 34.69 3.06 35.25 3.75 35.25H10.7925L7.6575 29.6075C7.4575 29.2462 7.4475 28.81 7.6325 28.4412L8.2925 27.12L6.6325 26.3963C6.315 26.2575 6.06875 25.9925 5.95375 25.6662L5.07125 23.1662C4.94 22.795 4.99125 22.3838 5.21 22.0563L5.215 22.05ZM23.75 29V31.5C23.75 32.19 24.31 32.75 25 32.75C25.69 32.75 26.25 32.19 26.25 31.5V29C26.25 28.31 25.69 27.75 25 27.75C24.31 27.75 23.75 28.31 23.75 29ZM15 0.25C10.5163 0.25 6.875 3.89125 6.875 8.375C6.875 12.8587 10.5163 16.5 15 16.5C19.4837 16.5 23.125 12.8587 23.125 8.375C23.125 3.89125 19.4837 0.25 15 0.25ZM15 2.75C18.105 2.75 20.625 5.27 20.625 8.375C20.625 11.48 18.105 14 15 14C11.895 14 9.375 11.48 9.375 8.375C9.375 5.27 11.895 2.75 15 2.75Z" fill="white"/>
                                    </svg>
                                </div>
                                <div  class="chart-num">
                                    <p>Total</p>
                                    <h2 class="font-w700 mb-0">

                                        {{ number_format($total_bus, 0, ',', ' ') }}


                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-6">
                            <div class="content-box">
                                <div class="teach-data icon-box icon-box-xl">
                                    <svg width="25" height="25" viewBox="0 0 30 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.9288 37.75H3.75C1.67875 37.75 0 36.0713 0 34V23.5863C0 21.7738 1.29625 20.2213 3.07875 19.8975C5.72125 19.4163 10.2775 18.5875 12.855 18.12C14.2737 17.8612 15.7263 17.8612 17.145 18.12C19.7225 18.5875 24.2788 19.4163 26.9213 19.8975C28.7038 20.2213 30 21.7738 30 23.5863C30 26.3125 30 31.0825 30 34C30 36.0713 28.3212 37.75 26.25 37.75H12.9288ZM24.785 22.05L24.79 22.0563C25.0088 22.3838 25.06 22.795 24.9287 23.1662L24.0462 25.6662C23.9312 25.9925 23.685 26.2575 23.3675 26.3963L21.7075 27.12L22.3675 28.4412C22.5525 28.81 22.5425 29.2462 22.3425 29.6075L19.2075 35.25H26.25C26.94 35.25 27.5 34.69 27.5 34C27.5 31.0825 27.5 26.3125 27.5 23.5863C27.5 22.9825 27.0675 22.465 26.4738 22.3562L24.785 22.05ZM21.3663 21.4275L16.6975 20.5788C15.575 20.375 14.425 20.375 13.3025 20.5788L8.63375 21.4275L7.63625 22.9238L8.13 24.3213L10.5 25.3537C10.8138 25.4912 11.0575 25.7512 11.175 26.0737C11.2925 26.3962 11.2712 26.7525 11.1175 27.0588L10.1625 28.9688L13.6525 35.25H16.3475L19.8375 28.9688L18.8825 27.0588C18.7288 26.7525 18.7075 26.3962 18.825 26.0737C18.9425 25.7512 19.1862 25.4912 19.5 25.3537L21.87 24.3213L22.3638 22.9238L21.3663 21.4275ZM5.215 22.05L3.52625 22.3562C2.9325 22.465 2.5 22.9825 2.5 23.5863V34C2.5 34.69 3.06 35.25 3.75 35.25H10.7925L7.6575 29.6075C7.4575 29.2462 7.4475 28.81 7.6325 28.4412L8.2925 27.12L6.6325 26.3963C6.315 26.2575 6.06875 25.9925 5.95375 25.6662L5.07125 23.1662C4.94 22.795 4.99125 22.3838 5.21 22.0563L5.215 22.05ZM23.75 29V31.5C23.75 32.19 24.31 32.75 25 32.75C25.69 32.75 26.25 32.19 26.25 31.5V29C26.25 28.31 25.69 27.75 25 27.75C24.31 27.75 23.75 28.31 23.75 29ZM15 0.25C10.5163 0.25 6.875 3.89125 6.875 8.375C6.875 12.8587 10.5163 16.5 15 16.5C19.4837 16.5 23.125 12.8587 23.125 8.375C23.125 3.89125 19.4837 0.25 15 0.25ZM15 2.75C18.105 2.75 20.625 5.27 20.625 8.375C20.625 11.48 18.105 14 15 14C11.895 14 9.375 11.48 9.375 8.375C9.375 5.27 11.895 2.75 15 2.75Z" fill="white"/>
                                    </svg>
                                </div>
                                <div class="chart-num">
                                    <p>Ce mois  </p>
                                    <h2 class="font-w700 mb-0">

                                        {{ number_format($total_cantine, 0, ',', ' ') }}



                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-6">
                            <div class="content-box">
                                <div class="event-data icon-box icon-box-xl">
                                    <svg width="25" height="25" viewBox="0 0 30 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.9288 37.75H3.75C1.67875 37.75 0 36.0713 0 34V23.5863C0 21.7738 1.29625 20.2213 3.07875 19.8975C5.72125 19.4163 10.2775 18.5875 12.855 18.12C14.2737 17.8612 15.7263 17.8612 17.145 18.12C19.7225 18.5875 24.2788 19.4163 26.9213 19.8975C28.7038 20.2213 30 21.7738 30 23.5863C30 26.3125 30 31.0825 30 34C30 36.0713 28.3212 37.75 26.25 37.75H12.9288ZM24.785 22.05L24.79 22.0563C25.0088 22.3838 25.06 22.795 24.9287 23.1662L24.0462 25.6662C23.9312 25.9925 23.685 26.2575 23.3675 26.3963L21.7075 27.12L22.3675 28.4412C22.5525 28.81 22.5425 29.2462 22.3425 29.6075L19.2075 35.25H26.25C26.94 35.25 27.5 34.69 27.5 34C27.5 31.0825 27.5 26.3125 27.5 23.5863C27.5 22.9825 27.0675 22.465 26.4738 22.3562L24.785 22.05ZM21.3663 21.4275L16.6975 20.5788C15.575 20.375 14.425 20.375 13.3025 20.5788L8.63375 21.4275L7.63625 22.9238L8.13 24.3213L10.5 25.3537C10.8138 25.4912 11.0575 25.7512 11.175 26.0737C11.2925 26.3962 11.2712 26.7525 11.1175 27.0588L10.1625 28.9688L13.6525 35.25H16.3475L19.8375 28.9688L18.8825 27.0588C18.7288 26.7525 18.7075 26.3962 18.825 26.0737C18.9425 25.7512 19.1862 25.4912 19.5 25.3537L21.87 24.3213L22.3638 22.9238L21.3663 21.4275ZM5.215 22.05L3.52625 22.3562C2.9325 22.465 2.5 22.9825 2.5 23.5863V34C2.5 34.69 3.06 35.25 3.75 35.25H10.7925L7.6575 29.6075C7.4575 29.2462 7.4475 28.81 7.6325 28.4412L8.2925 27.12L6.6325 26.3963C6.315 26.2575 6.06875 25.9925 5.95375 25.6662L5.07125 23.1662C4.94 22.795 4.99125 22.3838 5.21 22.0563L5.215 22.05ZM23.75 29V31.5C23.75 32.19 24.31 32.75 25 32.75C25.69 32.75 26.25 32.19 26.25 31.5V29C26.25 28.31 25.69 27.75 25 27.75C24.31 27.75 23.75 28.31 23.75 29ZM15 0.25C10.5163 0.25 6.875 3.89125 6.875 8.375C6.875 12.8587 10.5163 16.5 15 16.5C19.4837 16.5 23.125 12.8587 23.125 8.375C23.125 3.89125 19.4837 0.25 15 0.25ZM15 2.75C18.105 2.75 20.625 5.27 20.625 8.375C20.625 11.48 18.105 14 15 14C11.895 14 9.375 11.48 9.375 8.375C9.375 5.27 11.895 2.75 15 2.75Z" fill="white"/>
                                    </svg>
                                </div>
                                <div class="chart-num">
                                    <p>Cette semaine  </p>
                                    <h2 class="font-w700 mb-0">

                                        {{ number_format($total_livre, 0, ',', ' ') }}



                                    </h2>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>




    <!-- Row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="row">

                <div class="col-xl-12">
                    <div class="page-title flex-wrap">

                        <div>

                            <!-- Button trigger modal -->
                            <a href="{{url('/souscriptions/add')}}" type="button" href="#" id="lancerSouscription" class="btn btn-rounded btn-info"><span
                                    class="btn-icon-start text-info"><i class="fa fa-plus color-info"></i>
                    </span>Ajouter </a>



                        </div>
                    </div>
                </div>

                <!--column-->
                <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                    <div class="table-responsive full-data">
                        <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="example">
                            <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" class="form-check-input" id="checkAll" required="">
                                </th>
                              
                                <th>Eleve </th>
                                <th>Niveau   </th>
                                <th>Montant    </th>
                                <th>Date souscription  </th>
                                <th>Type   </th>
                                @if( $role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN )
                                <th>Saisi par  </th>


                                @endif


                                <th class="text-end">Action</th>
                            </tr>
                            </thead>
                            <tbody>


                            @foreach( $data as $souscription  )


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

                                            <h4>{{ $souscription ['nom_eleve'] }}</h4>
                                        </div>
                                    </td>

                                    <td><h6 class="mb-0">{{ $souscription ['niveau'] }} </h6></td>
                                    <td><h6 class="mb-0">

                                            {{ number_format($souscription ['montant_annuel_prevu'], 0, ',', ' ') }}



                                        </h6>
                                    </td>

                                    <td><h6 class="mb-0">{{ $souscription ['date_souscription'] }} </h6></td>

                                    <td>

                                        @if($souscription ['type_paiement'] === \App\Types\StatutSouscription::NON_ENCAISSE)

                                            <span class="badge badge-primary light badge-sm">Bus <span
                                                    class="ms-1 fa fa-redo"></span></span>

                                        @endif

                                        @if($souscription ['statut_Souscription'] === \App\Types\StatutSouscription::ENCAISSE)

                                                <span class="badge badge-success light badge-sm">Cantine <span
                                                        class="ms-1 fa fa-check"></span></span>

                                        @endif


                                            @if($souscription ['statut_Souscription'] === \App\Types\StatutSouscription::EN_ATTENTE_ANNULATION)

                                                <span class="badge badge-primary light badge-sm">Livre<span
                                                        class="ms-1 fa fa-redo"></span></span>

                                            @endif


                                         

                                    </td>

                                    @if( $role == \App\Types\Role::DIRECTEUR || $role == \App\Types\Role::ADMIN )

                                    <td><h6 class="mb-0">{{ $souscription ['utilisateur'] }} </h6></td>

                                    @endif

                                    <td>
                                        <div class="d-flex">
                                            <div class="dropdown custom-dropdown ">
                                                <div class="btn sharp tp-btn " data-bs-toggle="dropdown">
                                                    <svg width="18" height="6" viewBox="0 0 24 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M12.0012 0.359985C11.6543 0.359985 11.3109 0.428302 10.9904 0.561035C10.67 0.693767 10.3788 0.888317 10.1335 1.13358C9.88829 1.37883 9.69374 1.67 9.56101 1.99044C9.42828 2.31089 9.35996 2.65434 9.35996 3.00119C9.35996 3.34803 9.42828 3.69148 9.56101 4.01193C9.69374 4.33237 9.88829 4.62354 10.1335 4.8688C10.3788 5.11405 10.67 5.3086 10.9904 5.44134C11.3109 5.57407 11.6543 5.64239 12.0012 5.64239C12.7017 5.64223 13.3734 5.36381 13.8686 4.86837C14.3638 4.37294 14.6419 3.70108 14.6418 3.00059C14.6416 2.3001 14.3632 1.62836 13.8677 1.13315C13.3723 0.637942 12.7004 0.359826 12 0.359985H12.0012ZM3.60116 0.359985C3.25431 0.359985 2.91086 0.428302 2.59042 0.561035C2.26997 0.693767 1.97881 0.888317 1.73355 1.13358C1.48829 1.37883 1.29374 1.67 1.16101 1.99044C1.02828 2.31089 0.959961 2.65434 0.959961 3.00119C0.959961 3.34803 1.02828 3.69148 1.16101 4.01193C1.29374 4.33237 1.48829 4.62354 1.73355 4.8688C1.97881 5.11405 2.26997 5.3086 2.59042 5.44134C2.91086 5.57407 3.25431 5.64239 3.60116 5.64239C4.30165 5.64223 4.97339 5.36381 5.4686 4.86837C5.9638 4.37294 6.24192 3.70108 6.24176 3.00059C6.2416 2.3001 5.96318 1.62836 5.46775 1.13315C4.97231 0.637942 4.30045 0.359826 3.59996 0.359985H3.60116ZM20.4012 0.359985C20.0543 0.359985 19.7109 0.428302 19.3904 0.561035C19.07 0.693767 18.7788 0.888317 18.5336 1.13358C18.2883 1.37883 18.0937 1.67 17.961 1.99044C17.8283 2.31089 17.76 2.65434 17.76 3.00119C17.76 3.34803 17.8283 3.69148 17.961 4.01193C18.0937 4.33237 18.2883 4.62354 18.5336 4.8688C18.7788 5.11405 19.07 5.3086 19.3904 5.44134C19.7109 5.57407 20.0543 5.64239 20.4012 5.64239C21.1017 5.64223 21.7734 5.36381 22.2686 4.86837C22.7638 4.37294 23.0419 3.70108 23.0418 3.00059C23.0416 2.3001 22.7632 1.62836 22.2677 1.13315C21.7723 0.637942 21.1005 0.359826 20.4 0.359985H20.4012Z" fill="#A098AE"/>
                                                    </svg>
                                                </div>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item detailSouscription" href="" data-id="{{$souscription['id']}}">Voir détail </a>

                                                 
                                                        <a class="dropdown-item supprimerSouscription" href="#"  data-id="{{$souscription['id']}}">Supprimer</a>

                                         

                                                </div>
                                            </div>


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

    @include('admin.souscription.modal')
   


@endsection



@section('include')

@endsection


@section('js')

    <!-- Datatable -->
    <script src="{{asset('admin')}}/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="{{asset('admin')}}/js/plugins-init/datatables.init.js"></script>
    <!-- Apex Chart -->

    <!-- Dashboard 1 -->
    <script src="{{asset('admin')}}/js/dashboard/dashboard-2.js"></script>
    <script src="{{asset('admin')}}/vendor/chart.js/Chart.bundle.min.js"></script>
    <!-- Apex Chart -->
    <script src="{{asset('admin')}}/vendor/apexchart/apexchart.js"></script>

    <!-- Chart piety plugin files -->

    <script src="{{asset('admin')}}/vendor/jquery-nice-select/js/jquery.nice-select.min.js"></script>

    <script src="{{asset('admin')}}/vendor/wow-master/dist/wow.min.js"></script>


    <script src="{{asset('admin/js/sweetalert2/sweetalert2.min.js')}}"></script>
    <script>
        jQuery(document).ready(function() {


            //------------------------ Annuler   un souscription
            $("#annulerSuppresion").click(function(event) {
                event.preventDefault();

                annulerSuppresion()
            });



            //------------------------ Annuler   l ' affichage du detail de
            $("#fermerDetail").click(function(event) {
                event.preventDefault();

                fermerDetail()
            });


             $(document).on('click', '.supprimerSouscription', function(event) {

                event.preventDefault();
                let id = $(this).data('id');

                deleteConfirmation(id)

            });



          

            //------------------------ Afficher le  detail d unsouscription
            $(document).on('click', '.detailSouscription', function(event) {


                event.preventDefault();
                let id = $(this).data('id');
                let url = "/souscriptions/charger/" + id;


                $.get(url, function(data) {

                    console.log(data.result);

                    $('#defaultModalLabel').text('Détail d unsouscription      ');

                    let detail_modal = $('#detailSouscription');

                    $(detail_modal).find('form').find('input[name="reference"]').val(data.Souscription.reference);
                    $(detail_modal).find('form').find('input[name="eleve"]').val(data.eleve);
                    $(detail_modal).find('form').find('input[name="niveau"]').val(data.niveau);

                    $(detail_modal).find('form').find('input[name="montant_total"]').val(data.montant);


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
                            <td>${details[i].type_Souscription}</td>

                            `;

                    }


                    $('#liste_details').append(output);



                    $('#reference').prop('disabled', true);
                    $('#eleve').prop('disabled', true);
                    $('#niveau').prop('disabled', true);
                    $('#montant_total').prop('disabled', true);
                    $('#motif').val('');

                    let form = document.getElementById('form');
                    $(form).find('span.error-text').text('');

                    $('#idSouscription').val(data.Souscription.id);


                    $(detail_modal).modal('toggle');

                }, 'json')



            });






            



        });





        //------------------------ Fermer  le popup de detail
        function fermerDetail() {



            $('#detailSouscription').modal('toggle');
        }



       




    </script>


@endsection
