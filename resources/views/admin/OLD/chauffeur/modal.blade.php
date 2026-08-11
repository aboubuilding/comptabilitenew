
<div class="modal fade" id="addChauffeur" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <label for="exampleFormControlInput1" class="form-label">Nom </label>
                            <input type="text" class="form-control" id="nom" name="nom" ><br>




                        </div>

                        <span class="text-danger error-text nom_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Prénom  </label>
                            <input type="text" class="form-control" id="prenom" name="prenom" ><br>




                        </div>


                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Télephone  </label>
                            <input type="number" class="form-control" id="telephone" name="telephone" ><br>




                        </div>

                        <span class="text-danger error-text telephone_error"> </span>


                    </div>





                </div>
            </div>

            <input type="hidden" id="idChauffeur">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerChauffeur" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterChauffeur">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateChauffeur">Valider </button>
            </div>
        </div>


    </form>
</div>
