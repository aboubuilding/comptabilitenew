
<div class="modal fade" id="addFournisseur" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog modal-dialog-center" method="post" action="{{route('admin_fournisseurs_store')}}" enctype="multipart/form-data" id="form">

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
                            <label for="exampleFormControlInput1" class="form-label">Raison sociale  </label>
                            <input type="text" class="form-control" id="raison_social" name="raison_social" ><br>

                        </div>

                        <span class="text-danger error-text raison_social_error"> </span>

                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nom comtact   </label>
                            <input type="text" class="form-control" id="nom_contact" name="nom_contact" ><br>

                        </div>



                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Telephone   </label>
                            <input type="text" class="form-control" id="telephone_contact" name="telephone_contact" ><br>

                        </div>

                        <span class="text-danger error-text telephone_error"> </span>

                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Adresse    </label>
                            <textarea cols="10" class="form-control"  id="adresse" name="adresse"></textarea>

                        </div>

                    </div>




                </div>
            </div>

            <input type="hidden" id="idFournisseur">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerFournisseur" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterFournisseur">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateFournisseur">Valider </button>
            </div>
        </div>


    </form>
</div>
