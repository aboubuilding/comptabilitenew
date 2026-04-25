@php use App\Types\StatutValidation; @endphp
<!-- Modal -->
<div class="modal fade" id="deletePaiement" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog modal-dialog-center" method="post" action="" enctype="multipart/form-data" id="form" >

        @csrf


        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="defaultModalLabel">New Student Deatils</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">


                    <hr>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">   Référence  </label>
                            <input type="text" class="form-control" id="reference" name="reference" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">   Eleve   </label>
                            <input type="text" class="form-control" id="eleve" name="eleve" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Niveau de l' élève  </label>
                            <input type="text" class="form-control" id="niveau" name="niveau" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Montant total </label>
                            <input type="text" class="form-control" id="montant_total" name="montant_total" ><br>


                        </div>



                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Motif de suppression</label>
                           <textarea   class="form-control" cols="10" id="motif" name="motif"></textarea>

                        </div>

                        <span class="text-danger error-text motif_error"> </span>



                    </div>





                </div>
            </div>

            <input type="hidden" id="idPaiement">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerSuppresion" >Annuler </button>
                <button type="button" class="btn btn-primary" id="validerSuppression">Ajouter  </button>

            </div>
        </div>


    </form>
</div>
