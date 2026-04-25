
@extends('layout.app')

@section('title')

    Chiffre d affaire  | Classes

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
                                                <th>Cycle </th>
                                                <th>Niveau </th>
                                                <th>Libelle </th>
                                                <th>Total eleve  </th>
                                                <th>Solarite prév  </th>
                                                <th>Scolarité    </th>
                                                <th>Cantine prev   </th>
                                                <th>Cantine   </th>
                                                <th>Bus prev   </th>
                                                <th>Bus    </th>



                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                        @foreach( $data as $classe )


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

                                                        <h4>{{ $classe ['cycle'] }}</h4>
                                                    </div>
                                                </td>


                                                <td>
                                                    <div class="trans-list">

                                                        <h4>{{ $classe ['niveau'] }}</h4>
                                                    </div>
                                                </td>


                                                <td>
                                                    <div class="trans-list">

                                                        <h4>{{ $classe ['libelle'] }}</h4>
                                                    </div>
                                                </td>




                                                <td><h6 class="mb-0">
                                                    {{ $classe ['total_eleves'] }}

                                                </h6></td>
                                                <td>
                                                    <h6 class="mb-0">

                                                    {{ number_format($classe ['scolarite_previsionnel'], 0, ',', ' ') }}




                                                    </h6></td>
                                                <td><h6 class="mb-0">



                                                {{ number_format($classe ['paiement_scolarite'], 0, ',', ' ') }}



                                                </h6>

                                                </td>
                                                <td><h6 class="mb-0">
                                                {{ number_format($classe ['cantine_previsionnel'], 0, ',', ' ') }}





                                                </h6></td>
                                                <td><h6 class="mb-0">

                                                {{ number_format($classe ['paiement_cantine'], 0, ',', ' ') }}




                                                </h6></td>
                                                <td><h6 class="mb-0">


                                                {{ number_format($classe ['bus_previsionnel'], 0, ',', ' ') }}



                                                </h6></td>
                                                <td><h6 class="mb-0">


                                                {{ number_format($classe ['paiement_bus'], 0, ',', ' ') }}



                                                </h6></td>



                                                <td>
                                                    <div class="d-flex">
                                                        <a href="#" class="btn btn-danger shadow btn-xs sharp me-1 detailCycle" style="background-color: #1EA1F3; border: #1EA1F3" data-id="{{$classe['id']}}" title="Détails  " data-id="{{$classe['id']}}"><i class="fa fa-eye"></i></a>




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


@endsection



@section('js')

    <!--datatables-->
    <script src="{{asset('admin')}}/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="{{asset('admin')}}/js/plugins-init/datatables.init.js"></script>

    <!-- Dashboard 1 -->
    <script src="{{asset('admin')}}/vendor/wow-master/dist/wow.min.js"></script>

    <script src="{{asset('admin/js/sweetalert2/sweetalert2.min.js')}}"></script>



@endsection
