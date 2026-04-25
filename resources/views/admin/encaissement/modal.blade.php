@php use App\Types\StatutValidation; @endphp
    <!-- Modal -->
<div class="modal fade" id="addEncaissement" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog  modal-lg modal-dialog-center" method="post" action="{{route('admin_encaissements__store')}}" enctype="multipart/form-data" id="form" >

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

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label  class="form-label d-block">Paiements en attente    </label>
                            <select class="select2 col-xl-12"  id="paiement_id" name="paiement_id">
                                <option selected value="0">  </option>

                                @php

                                    $session = session()->get('LoginUser');
                                         $annee_id = $session['annee_id'];
                                        $compte_id = $session['compte_id'];

                                         $paiements = \App\Models\Paiement::getListe($annee_id, null, null, \App\Types\ModePaiement::ESPECE, \App\Types\StatutPaiement::NON_ENCAISSE);

                                @endphp

                                @foreach( $paiements  as $paiement )

                                    <option value="{{$paiement->id}}" >{{$paiement->reference}}</option>


                                @endforeach

                            </select>

                        </div>

                        <span class="text-danger error-text paiement_id_error"> </span>

                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nom de l eleve  </label>
                            <input type="text" class="form-control" id="eleve" name="eleve" ><br>


                        </div>



                    </div>


                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Niveau de l eleve   </label>
                            <input type="text" class="form-control" id="niveau" name="niveau" ><br>


                        </div>



                    </div>


                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Payeur    </label>
                            <input type="text" class="form-control" id="payeur" name="payeur" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Montant    </label>
                            <input type="text" class="form-control" id="montant" name="montant" ><br>


                        </div>



                    </div>


                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Montant encaissé    </label>
                            <input type="text" class="form-control" id="montant_encaisse" name="montant_encaisse" ><br>


                        </div>



                    </div>





                </div>
            </div>

            <input type="hidden" id="idEncaissement">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerEncaissement" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterEncaissement">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateEncaissement">Valider </button>
            </div>
        </div>


    </form>
</div>
