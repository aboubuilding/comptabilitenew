<!-- Modal -->
<div class="modal fade" id="addCaisse"  aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog modal-dialog-center" method="post" action="#" enctype="multipart/form-data" id="form">

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
                            <label for="exampleFormControlInput1" class="form-label">Libelle </label>
                            <input type="text" class="form-control" id="libelle" name="libelle" value="<?php echo ("Caisse-".date("Y-m-d")) ?>"><br>


                        </div>

                        <span class="text-danger error-text libelle_error"> </span>

                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Solde initial  </label>
                            <input type="text" class="form-control" id="solde_initial" name="solde_initial" ><br>




                        </div>

                        <span class="text-danger error-text solde_initial_error"> </span>

                    </div>


                    <div class="col-xl-12" id="div_cloturer">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Solde initial  </label>
                            <input type="text" class="form-control" id="solde_final" name="solde_final" ><br>




                        </div>

                        <span class="text-danger error-text solde_final_error"> </span>

                    </div>



                </div>
            </div>

            <input type="hidden" id="idCaisse">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerCaisse" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterCaisse">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="validerCloture">Cloturer  </button>
                <button type="button" class="btn btn-primary" id="updateCaisse">Valider </button>

            </div>
        </div>


    </form>
</div>
