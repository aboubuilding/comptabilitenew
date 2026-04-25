<!-- Modal -->
<div class="modal fade" id="addFraisecole" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog modal-dialog-center" method="post" action="{{route('admin_fraisecole_store')}}" enctype="multipart/form-data" id="form">

        @csrf


        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="defaultModalLabel">New Student Deatils</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Libelle</label>
                            <input type="text" class="form-control" id="libelle" name="libelle" ><br>




                        </div>

                        <span class="text-danger error-text libelle_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Montant</label>
                            <input type="text" class="form-control" id="montant" name="montant" ><br>




                        </div>

                        <span class="text-danger error-text montant_error"> </span>

                    </div>




                    <div class="col-xl-12">
                        <div class="mb-3">
                                <label  class="form-label d-block">Type de paiement </label>
                            <select class="default-select col-xl-12"  id="type_paiement" name="type_paiement">
                                <option selected>Choisir un  type de paiement  </option>role
                                <option value="{{\App\Types\TypePaiement::FRAIS_INSCRIPTION}}">Frais inscription </option>
                                <option value="{{\App\Types\TypePaiement::FRAIS_SCOLARITE}}">Frais scolarite  </option>
                                <option value="{{\App\Types\TypePaiement::LIVRE}}">Livre   </option>

                                <option value="{{\App\Types\TypePaiement::BUS}}">Bus  </option>
                                <option value="{{\App\Types\TypePaiement::CANTINE}}">Cantine </option>
                                <option value="{{\App\Types\TypePaiement::FRAIS_ASSURANCE}}">Frais assurance  </option>
                                <option value="{{\App\Types\TypePaiement::FRAIS_EXTRA_SCOLAIRE}}">Extra scolaire  </option>

                            </select>

                        </div>

                        <span class="text-danger error-text type_paiement_error"> </span>

                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label  class="form-label d-block">Type montant  </label>
                            <select class="default-select col-xl-12"  id="type_forfait" name="type_forfait">
                                <option selected>Choisir un  type montant  </option>role
                                <option value="1">Mensuel  </option>
                                <option value="2">Annuel  </option>


                            </select>

                        </div>

                        <span class="text-danger error-text type_forfait_error"> </span>

                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label  class="form-label d-block">Niveau  </label>
                            <select class="default-select col-xl-12"  id="niveau_id" name="niveau_id">
                                <option selected value="0">Choisir un  niveau   </option>

                                $niveaus = App\Models\Niveau::getListe();

                                @endphp

                                @foreach( $niveaus  as $niveau )

                                    <option value="{{$niveau->id}}" >{{$niveau->libelle}}</option>


                                @endforeach

                            </select>

                        </div>

                        <span class="text-danger error-text niveau_id_error"> </span>

                    </div>
                </div>
            </div>

            <input type="hidden" id="idFraisecole">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerFraisecole" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterFraisecole">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateFraisecole">Valider </button>
            </div>
        </div>


    </form>
</div>
