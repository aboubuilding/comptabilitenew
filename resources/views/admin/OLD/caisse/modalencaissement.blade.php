@php use App\Types\StatutValidation; @endphp
    <!-- Modal -->
<div class="modal fade" id="addEncaissement" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog  modal-xl modal-dialog-center" method="post" action="{{route('admin_encaissements__store')}}" enctype="multipart/form-data" id="formcaisse" >

        @csrf


        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="defaultModalLabel">New Student Deatils</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">

                    <h5 class="mb-0">Information du paiement  </h5>
                    <hr>

                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label  class="form-label d-block">Paiements en attente    </label>
                            <select class="select2 col-xl-12 single-select"  id="paiement_id" name="paiement_id">
                                <option selected value="0">  </option>

                                @php

                                    $session = session()->get('LoginUser');
                                         $annee_id = $session['annee_id'];
                                        $compte_id = $session['compte_id'];

                                         $paiements = \App\Models\Paiement::getListe(null, null, null, \App\Types\ModePaiement::ESPECE, \App\Types\StatutPaiement::NON_ENCAISSE);

                                @endphp

                                @foreach( $paiements  as $paiement )

                                    <option value="{{$paiement->id}}" >{{$paiement->reference}} | {{ $paiement->inscription->eleve->nom }} {{ $paiement->inscription->eleve->prenom }}</option>


                                @endforeach

                            </select>

                        </div>

                        <span class="text-danger error-text paiement_id_error"> </span>

                    </div>

                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nom de l eleve  </label>
                            <input type="text" disabled class="form-control" id="eleve" name="eleve" ><br>


                        </div>



                    </div>


                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Niveau de l eleve   </label>
                            <input type="text" disabled class="form-control" id="niveau" name="niveau" ><br>


                        </div>



                    </div>


                    <div class="col-xl-3">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Payeur    </label>
                            <input type="text" disabled class="form-control" id="payeur" name="payeur" ><br>


                        </div>



                    </div>

                    <div class="col-xl-3">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Montant  paiement    </label>
                            <input type="text" disabled class="form-control" id="montant" name="montant" ><br>


                        </div>

                        <span class="text-danger error-text montant_error"> </span>



                    </div>
                     <div class="col-xl-3">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Rémise   </label>
                            <input disabled type="text" class="form-control" id="remise" name="remise" ><br>


                        </div>

                        <span class="text-danger error-text remise_error"> </span>



                    </div>


                    <div class="col-xl-3">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Montant encaissé    </label>
                            <input type="text" class="form-control" id="montant_encaisse" name="montant_encaisse" ><br>


                        </div>

                        <span class="text-danger error-text montant_encaisse_error"> </span>



                    </div>

                     <h5 class="mb-0">Détails paiements  </h5>
                    <hr>

                     <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="example-student">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" class="form-check-input" id="checkAll" required="">
                                                </th>
                                                <th>Libelle</th>
                                                <th>Montant  </th>
                                                <th>Type  </th>


                                            </tr>
                                        </thead>
                                        <tbody id="liste_details">





                                        </tbody>
                                    </table>





                </div>
            </div>

            <input type="hidden" id="idEncaissement">
            <input type="hidden" id="idCaisseouvert">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerEncaissement" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterEncaissement">Ajouter </button>
                <button type="button" class="btn btn-primary" id="updateEncaissement">Valider </button>
            </div>
        </div>


    </form>
</div>
